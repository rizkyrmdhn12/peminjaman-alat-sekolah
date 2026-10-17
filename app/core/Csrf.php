<?php
/** Proteksi CSRF: token per sesi, dicek di index.php untuk SEMUA request POST. */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function valid(): bool
    {
        $sent = $_POST['_csrf'] ?? '';
        return is_string($sent) && $sent !== '' && hash_equals(self::token(), $sent);
    }
}
