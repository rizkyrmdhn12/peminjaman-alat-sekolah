<?php
/** Flash message (toast), old input, dan error per-field antar request. */
final class Flash
{
    private static ?array $old = null;
    private static ?array $errors = null;

    public static function set(string $type, string $msg): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
    }

    public static function pull(): array
    {
        $m = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $m;
    }

    public static function withInput(array $post, array $errors = []): void
    {
        unset($post['password'], $post['password_confirm'], $post['_csrf']);
        $_SESSION['_old'] = $post;
        $_SESSION['_errors'] = $errors;
    }

    private static function load(): void
    {
        if (self::$old === null) {
            self::$old = $_SESSION['_old'] ?? [];
            self::$errors = $_SESSION['_errors'] ?? [];
            unset($_SESSION['_old'], $_SESSION['_errors']);
        }
    }

    public static function old(string $key, $default = null)
    {
        self::load();
        return self::$old[$key] ?? $default;
    }

    public static function error(string $key): ?string
    {
        self::load();
        return self::$errors[$key] ?? null;
    }
}
