<?php
require_once __DIR__ . '/_ai_helpers.php';

try {
    if (!ai_method('POST')) exit;
    $db = ai_db();
    $user = ai_require_user($db);
    if ($user === null) exit;
    $data = ai_input();
    if (!ai_require_csrf($data)) exit;
    $sources = $data['sources'] ?? [];
    if (!is_array($sources) || !$sources) { ApiResponse::validation(['sources' => 'Select at least one source.']); exit; }

    $service = new AIKnowledgeService($db);
    $approved = 0; $processed = 0; $failed = 0; $errors = [];

    foreach (array_slice($sources, 0, 20) as $source) {
        $type = (string) ($source['resource_type'] ?? '');
        $id = (int) ($source['resource_id'] ?? 0);
        try {
            $result = $service->approveSource($user, $type, $id);
            $sourceId = (int) ($result['source_id'] ?? 0);
            $approved++;
            if ($sourceId > 0) {
                $service->processSource($sourceId);
                $db->prepare("UPDATE ai_ingestion_jobs SET status='completed',completed_at=UTC_TIMESTAMP(),error_code=NULL WHERE source_id=:source AND status IN ('pending','processing')")
                    ->execute(['source' => $sourceId]);
                $processed++;
            }
        } catch (Throwable $e) {
            $failed++;
            $errors[] = $type . ':' . $id;
        }
    }

    ApiResponse::success([
        'approved' => $approved,
        'worker' => ['checked' => $approved, 'processed' => $processed, 'failed' => $failed, 'skipped' => 0],
        'errors' => $errors,
    ], 'Selected AI knowledge built.');
} catch (Throwable $e) {
    ai_safe_error($e, 'build_avatar');
}
