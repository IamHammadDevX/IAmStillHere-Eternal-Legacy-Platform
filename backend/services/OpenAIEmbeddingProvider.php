<?php

require_once __DIR__ . '/EmbeddingProviderInterface.php';

class OpenAIEmbeddingProvider implements EmbeddingProviderInterface
{
    private string $apiKey;
    private string $model;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = trim((string) ($apiKey ?? getenv('OPENAI_API_KEY')));
        $this->model = trim((string) ($model ?? getenv('OPENAI_EMBEDDING_MODEL') ?: 'text-embedding-3-small'));
    }

    public function embed(array $texts): array
    {
        if ($this->apiKey === '') throw new RuntimeException('ai_api_key_missing');
        if (!function_exists('curl_init')) throw new RuntimeException('ai_http_unavailable');
        $texts = array_values(array_filter(array_map('strval', $texts), static fn(string $text): bool => trim($text) !== ''));
        if (!$texts) return [];

        $curl = curl_init('https://api.openai.com/v1/embeddings');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->apiKey, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['model' => $this->model, 'input' => $texts], JSON_UNESCAPED_SLASHES),
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_errno($curl);
        curl_close($curl);
        if ($body === false || $curlError !== 0) throw new RuntimeException('ai_provider_unavailable');
        if ($status === 429) throw new RuntimeException('ai_rate_limited');
        if ($status < 200 || $status >= 300) throw new RuntimeException('ai_provider_error');

        $decoded = json_decode($body, true);
        if (!is_array($decoded) || !isset($decoded['data']) || !is_array($decoded['data'])) throw new RuntimeException('ai_provider_response_invalid');
        usort($decoded['data'], static fn(array $a, array $b): int => ((int) $a['index']) <=> ((int) $b['index']));
        $vectors = array_map(static fn(array $item): array => array_map('floatval', $item['embedding'] ?? []), $decoded['data']);
        if (count($vectors) !== count($texts)) throw new RuntimeException('ai_provider_response_incomplete');
        return $vectors;
    }
}
