<?php

if (PHP_SAPI === 'cli') {
    ini_set('session.save_path', sys_get_temp_dir());
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/VideoThumbnailHelper.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script can only be run from the command line.\n";
    exit(1);
}

$limit = 20;
foreach ($argv as $arg) {
    if (strpos($arg, '--limit=') === 0) {
        $limit = max(1, min(100, (int) substr($arg, 8)));
    }
}

$summary = [
    'processed' => 0,
    'generated' => 0,
    'skipped' => 0,
    'failed' => 0,
];

try {
    $database = new Database();
    $connection = $database->getConnection();

    $statement = $connection->prepare(
        "SELECT id, file_path, file_type
         FROM memories
         WHERE status = 'active'
         AND (video_thumbnail_path IS NULL OR video_thumbnail_path = '')
         AND (file_type LIKE 'video/%'
              OR LOWER(file_path) REGEXP '\\.(mp4|avi|mov|mkv|webm|mpeg|mpg|3gp|flv|wmv)$')
         ORDER BY id ASC
         LIMIT :limit"
    );
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    $memories = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($memories as $memory) {
        $summary['processed']++;

        $videoPath = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . $memory['file_path'];
        if (!is_file($videoPath)) {
            $summary['skipped']++;
            echo "Skipped memory {$memory['id']}: video file missing\n";
            continue;
        }

        $thumbnailPath = VideoThumbnailHelper::generate($videoPath, $memory['file_path']);
        if ($thumbnailPath === null) {
            $summary['failed']++;
            echo "Failed memory {$memory['id']}: thumbnail not generated\n";
            continue;
        }

        $update = $connection->prepare(
            "UPDATE memories
             SET video_thumbnail_path = :thumbnail_path
             WHERE id = :id"
        );
        $update->execute([
            'thumbnail_path' => $thumbnailPath,
            'id' => (int) $memory['id'],
        ]);

        $summary['generated']++;
        echo "Generated memory {$memory['id']}: {$thumbnailPath}\n";
    }

    echo "Summary: processed={$summary['processed']} generated={$summary['generated']} skipped={$summary['skipped']} failed={$summary['failed']}\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, "Backfill failed: " . $exception->getMessage() . "\n");
    exit(1);
}
