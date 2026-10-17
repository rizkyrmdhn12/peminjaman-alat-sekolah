<?php
/** Log error di server (storage/logs). Detail teknis TIDAK ditampilkan ke pengguna. */
final class AppLog
{
    public static function error($e, string $context = ''): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ';
        if ($e instanceof Throwable) {
            $line .= get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine();
        } else {
            $line .= (string)$e;
        }
        if ($context !== '') {
            $line .= ' | ' . $context;
        }
        $line .= ' | ' . ($_SERVER['REQUEST_METHOD'] ?? 'CLI') . ' ' . ($_SERVER['REQUEST_URI'] ?? '') . PHP_EOL;
        $dir = BASE_PATH . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (@file_put_contents($dir . '/app-' . date('Y-m') . '.log', $line, FILE_APPEND | LOCK_EX) === false) {
            error_log(trim($line));
        }
    }
}
