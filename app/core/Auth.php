<?php
/**
 * Autentikasi + otorisasi. Role SELALU dibaca dari session yang disegarkan dari database
 * pada setiap request (Auth::boot) - tidak pernah dari URL, POST, cookie biasa, atau JavaScript.
 */
final class Auth
{
    private static ?array $matrix = null;

    public static function boot(): void
    {
        $id = (int)($_SESSION['auth']['user_id'] ?? 0);
        if ($id <= 0) {
            return;
        }
        $u = (new UserModel())->findAuth($id);
        if (!$u || $u['status'] !== 'aktif') {
            self::destroy();
            return;
        }
        $_SESSION['auth'] = [
            'user_id' => (int)$u['id_user'],
            'username' => $u['username'],
            'nama_lengkap' => $u['nama_lengkap'],
            'role' => $u['role'],
        ];
    }

    public static function login(array $u): void
    {
        session_regenerate_id(true);
        $_SESSION['auth'] = [
            'user_id' => (int)$u['id_user'],
            'username' => $u['username'],
            'nama_lengkap' => $u['nama_lengkap'],
            'role' => $u['role'],
        ];
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return !empty($_SESSION['auth']['user_id']);
    }

    public static function user(): ?array
    {
        return self::check() ? $_SESSION['auth'] : null;
    }

    public static function id(): int
    {
        return (int)($_SESSION['auth']['user_id'] ?? 0);
    }

    public static function role(): ?string
    {
        return $_SESSION['auth']['role'] ?? null;
    }

    public static function can(string $permission): bool
    {
        $role = self::role();
        if ($role === null) {
            return false;
        }
        if (self::$matrix === null) {
            self::$matrix = require BASE_PATH . '/config/permissions.php';
        }
        return in_array($permission, self::$matrix[$role] ?? [], true);
    }

    public static function canAny(array $permissions): bool
    {
        foreach ($permissions as $p) {
            if (self::can($p)) {
                return true;
            }
        }
        return false;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            abort(401, 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        }
    }

    public static function requireRole(array $roles): void
    {
        self::requireLogin();
        if (!in_array(self::role(), $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman tersebut.');
        }
    }

    public static function requirePermission($permission): void
    {
        self::requireLogin();
        $perms = (array)$permission;
        if (!self::canAny($perms)) {
            abort(403, 'Anda tidak memiliki akses ke halaman tersebut.');
        }
    }

    public static function homeRoute(): string
    {
        return 'dashboard-' . (self::role() ?? 'peminjam');
    }
}
