<?php
require_once __DIR__ . '/../helpers/Logger.php';

class GiftService
{
    private PDO $db;
    private string $apiUrl;
    private string $apiKey;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->apiUrl = rtrim((string)getenv('PHOOLWALA_API_URL'), '/');
        $this->apiKey = trim((string)getenv('PHOOLWALA_API_KEY'));
    }

    public function catalog(): array
    {
        $this->requireConfig();
        return $this->provider('GET', '/catalog');
    }

    public function createOrder(int $owner, array $data): array
    {
        $safe = $this->validateOrder($owner, $data);
        $key = $safe['idempotency_key'];
        $existing = $this->findByIdempotency($owner, $key);
        if ($existing) return $existing;

        $this->requireConfig();
        $payload = [
            'gift_id' => $safe['gift_external_id'],
            'recipient' => [
                'name' => $safe['recipient_name'],
                'email' => $safe['recipient_email'],
                'phone' => $safe['recipient_phone'],
                'address' => $safe['recipient_address'],
            ],
            'delivery_at' => gmdate('c', strtotime($safe['delivery_at'])),
            'occasion' => $safe['occasion'],
            'message' => $safe['message_text'],
            'idempotency_key' => $key,
        ];

        $provider = $this->provider('POST', '/orders', $payload, $key);
        $externalId = (string)($provider['order_id'] ?? $provider['id'] ?? '');
        if ($externalId === '') throw new RuntimeException('gift_provider_order_missing');
        $status = $this->mapStatus((string)($provider['status'] ?? 'scheduled'));

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO gift_orders(owner_id,recipient_user_id,recipient_name,recipient_email,recipient_phone,recipient_address,occasion,gift_external_id,gift_name,gift_price,gift_currency,message_id,message_text,delivery_at,status,external_order_id,idempotency_key,provider_payload) VALUES(:owner,:recipient_user_id,:recipient_name,:recipient_email,:recipient_phone,:recipient_address,:occasion,:gift_external_id,:gift_name,:gift_price,:gift_currency,:message_id,:message_text,:delivery_at,:status,:external_order_id,:idempotency_key,:provider_payload)");
            $stmt->execute($safe + ['owner'=>$owner,'status'=>$status,'external_order_id'=>$externalId,'provider_payload'=>json_encode($this->redact($provider),JSON_UNESCAPED_SLASHES)]);
            $id = (int)$this->db->lastInsertId();
            $this->logEvent($id, 'created', $status, $provider);
            $this->db->commit();
            return $this->get($owner, $id);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function list(int $owner, bool $admin=false): array
    {
        if ($admin) {
            $stmt = $this->db->query("SELECT * FROM gift_orders WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 100");
            return array_map([$this,'format'], $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        $stmt = $this->db->prepare("SELECT * FROM gift_orders WHERE owner_id=:owner AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 50");
        $stmt->execute(['owner'=>$owner]);
        return array_map([$this,'format'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function cancel(int $owner, int $id): array
    {
        $order = $this->row($owner, $id);
        if (!$order) throw new RuntimeException('gift_order_not_found');
        if (!in_array($order['status'], ['scheduled','pending_payment','placed'], true)) throw new InvalidArgumentException('Order cannot be cancelled now.');
        $this->requireConfig();
        $provider = $this->provider('POST', '/orders/' . rawurlencode((string)$order['external_order_id']) . '/cancel', [], $order['idempotency_key'].'-cancel');
        $status = $this->mapStatus((string)($provider['status'] ?? 'cancelled'));
        $this->db->prepare("UPDATE gift_orders SET status=:status, provider_payload=:payload, updated_at=UTC_TIMESTAMP() WHERE id=:id AND owner_id=:owner")->execute(['status'=>$status,'payload'=>json_encode($this->redact($provider),JSON_UNESCAPED_SLASHES),'id'=>$id,'owner'=>$owner]);
        $this->logEvent($id, 'cancel', $status, $provider);
        return $this->get($owner,$id);
    }

    public function updateFromWebhook(array $payload): void
    {
        $external = (string)($payload['order_id'] ?? $payload['id'] ?? '');
        if ($external === '') throw new InvalidArgumentException('Missing order id.');
        $status = $this->mapStatus((string)($payload['status'] ?? 'processing'));
        $stmt = $this->db->prepare("SELECT id FROM gift_orders WHERE external_order_id=:external LIMIT 1");
        $stmt->execute(['external'=>$external]);
        $id = (int)$stmt->fetchColumn();
        if ($id <= 0) throw new RuntimeException('gift_order_not_found');
        $this->db->prepare("UPDATE gift_orders SET status=:status, provider_payload=:payload, updated_at=UTC_TIMESTAMP() WHERE id=:id")->execute(['status'=>$status,'payload'=>json_encode($this->redact($payload),JSON_UNESCAPED_SLASHES),'id'=>$id]);
        $this->logEvent($id, 'webhook', $status, $payload);
    }

    public function verifyWebhook(string $body, string $signature): bool
    {
        $secret = (string)getenv('PHOOLWALA_WEBHOOK_SECRET');
        if ($secret === '' || $signature === '') return false;
        $expected = hash_hmac('sha256', $body, $secret);
        return hash_equals($expected, $signature) || hash_equals('sha256='.$expected, $signature);
    }

    private function validateOrder(int $owner, array $data): array
    {
        $recipientId = (int)($data['recipient_user_id'] ?? 0);
        $name = mb_substr(trim(strip_tags((string)($data['recipient_name'] ?? ''))),0,255);
        $email = trim((string)($data['recipient_email'] ?? ''));
        $phone = mb_substr(trim(strip_tags((string)($data['recipient_phone'] ?? ''))),0,50);
        $address = mb_substr(trim(strip_tags((string)($data['recipient_address'] ?? ''))),0,2000);
        if ($recipientId > 0) {
            $s=$this->db->prepare("SELECT full_name,email FROM users WHERE id=:id AND status='active' LIMIT 1");$s->execute(['id'=>$recipientId]);$u=$s->fetch(PDO::FETCH_ASSOC);
            if(!$u || $this->blocked($owner,$recipientId)) throw new InvalidArgumentException('Recipient not allowed.');
            $name = $name ?: (string)$u['full_name'];
            $email = $email ?: (string)$u['email'];
        }
        if ($name === '') throw new InvalidArgumentException('Recipient name required.');
        if ($email !== '' && !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Invalid recipient email.');
        if ($address === '') throw new InvalidArgumentException('Delivery address required.');
        $occasion=(string)($data['occasion']??'custom'); if(!in_array($occasion,['birthday','anniversary','graduation','wedding','new_job','new_baby','custom'],true))$occasion='custom';
        $giftId=mb_substr(trim((string)($data['gift_external_id']??'')),0,191); if($giftId==='') throw new InvalidArgumentException('Gift required.');
        $giftName=mb_substr(trim(strip_tags((string)($data['gift_name']??'Selected gift'))),0,255);
        $delivery=$this->utc((string)($data['delivery_at']??'')); if(!$delivery || strtotime($delivery)<=time()) throw new InvalidArgumentException('Future delivery date required.');
        $messageId=(int)($data['message_id']??0); $message=mb_substr(trim(strip_tags((string)($data['message_text']??''))),0,2000);
        if($messageId>0){$m=$this->db->prepare("SELECT edited_message,generated_message FROM ai_personalized_messages WHERE id=:id AND owner_id=:owner AND status IN ('draft','approved','scheduled','sent') AND deleted_at IS NULL LIMIT 1");$m->execute(['id'=>$messageId,'owner'=>$owner]);$row=$m->fetch(PDO::FETCH_ASSOC); if(!$row) throw new InvalidArgumentException('Message not found.'); $message=$message ?: (string)($row['edited_message'] ?: $row['generated_message']);}
        $key=mb_substr(trim((string)($data['idempotency_key']??'')),0,191); if($key==='')$key=hash('sha256',$owner.'|'.$giftId.'|'.$email.'|'.$delivery.'|'.$message);
        return ['recipient_user_id'=>$recipientId?:null,'recipient_name'=>$name,'recipient_email'=>$email?:null,'recipient_phone'=>$phone?:null,'recipient_address'=>$address,'occasion'=>$occasion,'gift_external_id'=>$giftId,'gift_name'=>$giftName,'gift_price'=>isset($data['gift_price'])?(float)$data['gift_price']:null,'gift_currency'=>mb_substr((string)($data['gift_currency']??''),0,10)?:null,'message_id'=>$messageId?:null,'message_text'=>$message?:null,'delivery_at'=>$delivery,'idempotency_key'=>$key];
    }

    private function provider(string $method, string $path, ?array $payload=null, ?string $idempotency=null): array
    {
        $url = $this->apiUrl . $path;
        $headers = ['Authorization: Bearer '.$this->apiKey, 'Accept: application/json'];
        if ($idempotency) $headers[] = 'Idempotency-Key: '.$idempotency;
        $ch = curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>$headers]);
        if ($method === 'POST') { $headers[]='Content-Type: application/json'; curl_setopt($ch,CURLOPT_HTTPHEADER,$headers); curl_setopt($ch,CURLOPT_POST,true); curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($payload??[],JSON_UNESCAPED_SLASHES)); }
        $body = curl_exec($ch); $code = (int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
        if ($body === false || $code >= 400) { Logger::error('Phoolwala API failed',['status'=>$code,'error'=>$err]); throw new RuntimeException('gift_provider_failed'); }
        $json = json_decode((string)$body,true); if(!is_array($json)) throw new RuntimeException('gift_provider_bad_response');
        return $json;
    }
    private function requireConfig(): void { if($this->apiUrl==='' || $this->apiKey==='') throw new RuntimeException('gift_provider_not_configured'); }
    private function row(int $owner,int $id): ?array {$s=$this->db->prepare('SELECT * FROM gift_orders WHERE id=:id AND owner_id=:owner AND deleted_at IS NULL LIMIT 1');$s->execute(['id'=>$id,'owner'=>$owner]);$r=$s->fetch(PDO::FETCH_ASSOC);return $r?:null;}
    private function get(int $owner,int $id): array {$r=$this->row($owner,$id); if(!$r) throw new RuntimeException('gift_order_not_found'); return $this->format($r);}
    private function findByIdempotency(int $owner,string $key): ?array {$s=$this->db->prepare('SELECT * FROM gift_orders WHERE owner_id=:owner AND idempotency_key=:k AND deleted_at IS NULL LIMIT 1');$s->execute(['owner'=>$owner,'k'=>$key]);$r=$s->fetch(PDO::FETCH_ASSOC);return $r?$this->format($r):null;}
    private function logEvent(int $id,string $type,string $status,array $payload): void {$this->db->prepare('INSERT INTO gift_order_events(gift_order_id,event_type,status,payload) VALUES(:id,:type,:status,:payload)')->execute(['id'=>$id,'type'=>$type,'status'=>$status,'payload'=>json_encode($this->redact($payload),JSON_UNESCAPED_SLASHES)]);}
    private function format(array $r): array {foreach(['id','owner_id','recipient_user_id','message_id','automation_rule_id'] as $k)$r[$k]=$r[$k]!==null?(int)$r[$k]:null;unset($r['provider_payload']);return $r;}
    private function mapStatus(string $s): string {$s=strtolower($s);return in_array($s,['draft','pending_payment','scheduled','processing','placed','delivered','failed','cancelled'],true)?$s:'processing';}
    private function utc(string $v): ?string {try{return trim($v)===''?null:(new DateTimeImmutable($v))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');}catch(Throwable $e){return null;}}
    private function blocked(int $a,int $b): bool {$s=$this->db->prepare("SELECT id FROM friendships WHERE ((user_id=:a AND friend_id=:b) OR (user_id=:b AND friend_id=:a)) AND status='blocked' LIMIT 1");$s->execute(['a'=>$a,'b'=>$b]);return(bool)$s->fetchColumn();}
    private function redact(array $payload): array {unset($payload['api_key'],$payload['card'],$payload['card_number'],$payload['cvv'],$payload['payment_token']);return $payload;}
}
