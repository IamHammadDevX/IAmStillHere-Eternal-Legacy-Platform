<?php
require_once __DIR__ . '/_journey_helpers.php';
try{
    if($_SERVER['REQUEST_METHOD']!=='GET'){ApiResponse::send(false,[],'Method not allowed.',[],405);exit;}
    $db=journeys_db(); $viewer=journeys_user_id(); $profile=(int)($_GET['user_id']??0); $page=max(1,(int)($_GET['page']??1)); $limit=min(25,max(1,(int)($_GET['limit']??10))); $offset=($page-1)*$limit;
    if($profile>0 && $viewer!==null && $profile===$viewer){
        $sql="SELECT DISTINCT j.*, u.full_name owner_name, u.profile_photo owner_photo, jp.status AS participant_status FROM journeys j INNER JOIN users u ON u.id=j.owner_id LEFT JOIN journey_participants jp ON jp.journey_id=j.id AND jp.user_id=:viewer WHERE j.deleted_at IS NULL AND (j.owner_id=:profile OR jp.user_id=:viewer) ORDER BY j.updated_at DESC, j.id DESC LIMIT :limit OFFSET :offset";
        $s=$db->prepare($sql); $s->bindValue(':viewer',$viewer,PDO::PARAM_INT); $s->bindValue(':profile',$profile,PDO::PARAM_INT);
    } else {
        $where='j.deleted_at IS NULL'; if($profile>0)$where.=' AND j.owner_id=:profile';
        $sql="SELECT j.*, u.full_name owner_name, u.profile_photo owner_photo, NULL AS participant_status FROM journeys j INNER JOIN users u ON u.id=j.owner_id WHERE $where ORDER BY j.updated_at DESC, j.id DESC LIMIT :limit OFFSET :offset";
        $s=$db->prepare($sql); if($profile>0)$s->bindValue(':profile',$profile,PDO::PARAM_INT);
    }
    $s->bindValue(':limit',$limit,PDO::PARAM_INT); $s->bindValue(':offset',$offset,PDO::PARAM_INT); $s->execute();
    $visible=[];
    foreach($s->fetchAll(PDO::FETCH_ASSOC) as $j){
        $pending=$viewer!==null && (int)$j['owner_id']!==$viewer && ($j['participant_status']??'')==='pending';
        if($pending || journeys_can_view($db,$j,$viewer)) $visible[]=$j;
    }
    $formatted=array_map(function($j) use($db){ $f=journeys_format($db,$j); $f['participant_status']=$j['participant_status']??null; return $f; },$visible);
    ApiResponse::success(['journeys'=>$formatted,'pagination'=>['current_page'=>$page,'per_page'=>$limit,'total_items'=>count($formatted),'total_pages'=>1]],'Journeys loaded.');
}catch(Throwable $e){Logger::error('Journey list failed',['error'=>$e->getMessage()]);ApiResponse::serverError('Unable to load journeys.');}
?>
