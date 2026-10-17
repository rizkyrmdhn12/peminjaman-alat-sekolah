<?php
/**
 * Front controller tunggal. Semua request melewati: validasi route -> otorisasi (RBAC)
 * -> verifikasi CSRF (POST) -> sinkronisasi status -> controller.
 */
define('BASE_PATH', __DIR__);
require BASE_PATH . '/config/config.php';
date_default_timezone_set(APP_TIMEZONE);
require BASE_PATH . '/app/core/helpers.php';

spl_autoload_register(function (string $class): void {
    foreach (['core', 'controllers', 'models'] as $dir) {
        $file = BASE_PATH . "/app/$dir/$class.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

ini_set('log_errors', '1');
ini_set('display_errors', APP_ENV === 'development' ? '1' : '0');
error_reporting(E_ALL);

// Warning/notice dicatat ke log server (tidak menampilkan detail ke pengguna).
set_error_handler(function (int $no, string $str, string $file, int $line): bool {
    if (!(error_reporting() & $no)) return false;
    AppLog::error("PHP[$no] $str @ " . basename($file) . ":$line");
    return true;
});
set_exception_handler(function (Throwable $e): void {
    AppLog::error($e);
    try {
        abort(500, APP_ENV === 'development' ? $e->getMessage() : '');
    } catch (Throwable $e2) {
        if (!headers_sent()) http_response_code(500);
        echo 'Terjadi kesalahan. Silakan coba lagi.';
        exit;
    }
});

session_name('SANGKURIANG_SESS');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

Auth::boot();

$route = $_GET['route'] ?? 'home';
if (!is_string($route) || !preg_match('/^[a-z0-9\-]{1,40}$/', $route)) {
    abort(404);
}
$routes = require BASE_PATH . '/config/routes.php';
if (!isset($routes[$route])) {
    abort(404);
}
[$class, $method, $permission, $verb] = $routes[$route];

$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($verb !== 'ANY' && $httpMethod !== $verb && !($verb === 'GET' && $httpMethod === 'HEAD')) {
    abort(405);
}
if ($permission !== null) {
    Auth::requireLogin();
    if ($permission !== 'auth') {
        Auth::requirePermission($permission);
    }
}
if ($httpMethod === 'POST' && !Csrf::valid()) {
    abort(403, 'Sesi formulir tidak valid atau kedaluwarsa. Muat ulang halaman lalu coba lagi.');
}
if (Auth::check()) {
    SyncService::run();
}
(new $class())->$method();
