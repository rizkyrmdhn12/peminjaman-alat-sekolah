<?php
/** Base controller: render, redirect, input, logging aktivitas. */
abstract class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        View::render($view, $data, $layout);
    }

    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    protected function id(string $key = 'id'): int
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? '';
        return (is_string($v) && ctype_digit($v)) ? (int)$v : 0;
    }

    protected function post(string $key): string
    {
        $v = $_POST[$key] ?? '';
        return is_string($v) ? trim($v) : '';
    }

    protected function get(string $key): string
    {
        $v = $_GET[$key] ?? '';
        return is_string($v) ? trim($v) : '';
    }

    /** Nilai GET yang harus salah satu dari daftar (whitelist), selain itu string kosong. */
    protected function enumGet(string $key, array $allowed): string
    {
        $v = $this->get($key);
        return in_array($v, $allowed, true) ? $v : '';
    }

    protected function dateGet(string $key): string
    {
        $v = $this->get($key);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            return $v;
        }
        return '';
    }

    protected function page(): int
    {
        $p = $this->get('page');
        return ctype_digit($p) && (int)$p > 0 ? (int)$p : 1;
    }

    /** Ambil hanya kunci yang diizinkan dari POST (mencegah mass-assignment, mis. role/id_user). */
    protected function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = $this->post($k);
        }
        return $out;
    }

    protected function log(string $aksi, string $modul, ?int $ref, string $ket = ''): void
    {
        try {
            (new LogModel())->add(Auth::id() ?: null, $aksi, $modul, $ref, $ket);
        } catch (Throwable $e) {
            AppLog::error($e, 'gagal menulis log aktivitas');
        }
    }

    /** Pesan bisnis ditampilkan apa adanya; error teknis hanya dicatat di server. */
    protected function fail(Throwable $e, string $fallbackRoute, array $params = []): void
    {
        if ($e instanceof BusinessException) {
            Flash::set('error', $e->getMessage());
        } else {
            AppLog::error($e);
            Flash::set('error', 'Terjadi kesalahan. Silakan coba lagi.');
        }
        redirect($fallbackRoute, $params);
    }
}
