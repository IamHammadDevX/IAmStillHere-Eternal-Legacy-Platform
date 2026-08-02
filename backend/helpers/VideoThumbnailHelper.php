<?php

require_once __DIR__ . '/Logger.php';

class VideoThumbnailHelper
{
    private const THUMBNAIL_EXTENSION = 'jpg';
    private const THUMBNAIL_MIME_TYPE = 'image/jpeg';
    private const CAPTURE_TIMESTAMP = '00:00:02';
    private const TIMEOUT_SECONDS = 15;

    public static function generate(string $videoPath, string $sourceFilename): ?string
    {
        if (!self::isValidInputVideo($videoPath)) {
            Logger::warning('Video thumbnail skipped: invalid input video');
            return null;
        }

        $ffmpeg = self::findFfmpeg();
        if ($ffmpeg === null) {
            Logger::info('Video thumbnail skipped: FFmpeg not available');
            return null;
        }

        $thumbnailDirectory = self::thumbnailDirectory();
        if (!is_dir($thumbnailDirectory) && !@mkdir($thumbnailDirectory, 0775, true)) {
            Logger::warning('Video thumbnail skipped: thumbnail directory unavailable');
            return null;
        }

        $baseName = pathinfo($sourceFilename, PATHINFO_FILENAME);
        $safeBaseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        $thumbnailFilename = 'thumb_' . $safeBaseName . '_' . bin2hex(random_bytes(6)) . '.' . self::THUMBNAIL_EXTENSION;
        $thumbnailPath = $thumbnailDirectory . DIRECTORY_SEPARATOR . $thumbnailFilename;

        $command = self::buildCommand($ffmpeg, $videoPath, $thumbnailPath);
        $exitCode = self::runCommand($command);

        if ($exitCode !== 0 || !self::isValidThumbnail($thumbnailPath)) {
            if (is_file($thumbnailPath)) {
                @unlink($thumbnailPath);
            }

            Logger::warning('Video thumbnail generation failed', ['exit_code' => $exitCode]);
            return null;
        }

        return 'thumbnails/' . $thumbnailFilename;
    }

    public static function thumbnailFilePath(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', $relativePath);
        if (!preg_match('/^thumbnails\/[a-zA-Z0-9_.-]+\.jpe?g$/', $normalized)) {
            return null;
        }

        return rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
    }

    public static function isFfmpegAvailable(): bool
    {
        return self::findFfmpeg() !== null;
    }

    private static function isValidInputVideo(string $videoPath): bool
    {
        return is_file($videoPath) && is_readable($videoPath) && filesize($videoPath) > 0;
    }

    private static function thumbnailDirectory(): string
    {
        return rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'thumbnails';
    }

    private static function findFfmpeg(): ?string
    {
        $candidates = [];

        $envPath = getenv('FFMPEG_PATH');
        if (is_string($envPath) && $envPath !== '') {
            $candidates[] = $envPath;
        }

        if (defined('FFMPEG_PATH')) {
            $candidates[] = FFMPEG_PATH;
        }

        $candidates[] = 'ffmpeg';
        $candidates[] = 'C:\\ffmpeg\\bin\\ffmpeg.exe';
        $candidates[] = 'C:\\xampp\\ffmpeg\\bin\\ffmpeg.exe';

        foreach ($candidates as $candidate) {
            if ($candidate === 'ffmpeg' && self::commandExists('ffmpeg')) {
                return 'ffmpeg';
            }

            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private static function commandExists(string $command): bool
    {
        $checkCommand = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? 'where ' . escapeshellarg($command) . ' 2>NUL'
            : 'command -v ' . escapeshellarg($command);

        $output = [];
        $exitCode = 1;
        @exec($checkCommand, $output, $exitCode);

        return $exitCode === 0;
    }

    private static function buildCommand(string $ffmpeg, string $videoPath, string $thumbnailPath): string
    {
        return escapeshellarg($ffmpeg)
            . ' -y -ss ' . escapeshellarg(self::CAPTURE_TIMESTAMP)
            . ' -i ' . escapeshellarg($videoPath)
            . ' -frames:v 1 -q:v 3 '
            . escapeshellarg($thumbnailPath);
    }

    private static function runCommand(string $command): int
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(self::TIMEOUT_SECONDS + 5);
        }

        $output = [];
        $exitCode = 1;
        @exec($command, $output, $exitCode);

        return $exitCode;
    }

    private static function isValidThumbnail(string $thumbnailPath): bool
    {
        if (!is_file($thumbnailPath) || filesize($thumbnailPath) <= 0) {
            return false;
        }

        $info = @getimagesize($thumbnailPath);
        return is_array($info) && ($info['mime'] ?? '') === self::THUMBNAIL_MIME_TYPE;
    }
}
