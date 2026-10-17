<?php
/** Helper global untuk view & controller. */

function e($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $route = 'home', array $params = []): string
{
    return 'index.php?' . http_build_query(['route' => $route] + $params);
}

function redirect(string $route, array $params = []): void
{
    header('Location: ' . url($route, $params));
    exit;
}

function asset(string $path): string
{
    return 'assets/' . ltrim($path, '/');
}

function rupiah($n): string
{
    return 'Rp' . number_format((float)$n, 0, ',', '.');
}

function tgl($d, bool $jam = false): string
{
    if (!$d) return '-';
    $t = strtotime((string)$d);
    return $t ? date($jam ? 'd/m/Y H:i' : 'd/m/Y', $t) : '-';
}

function jam(?string $t): string
{
    return $t ? substr($t, 0, 5) : '-';
}

function label(?string $v): string
{
    return $v === null || $v === '' ? '-' : ucfirst(str_replace('_', ' ', $v));
}

function badge(?string $status): string
{
    $map = [
        'tersedia' => 'ok', 'dikembalikan' => 'ok', 'diselesaikan' => 'ok', 'baik' => 'ok', 'aktif' => 'ok',
        'dipinjam' => 'warn', 'rusak_ringan' => 'warn', 'belum_diselesaikan' => 'warn', 'disetujui' => 'info',
        'diajukan' => 'info', 'online' => 'info',
        'rusak' => 'danger', 'rusak_berat' => 'danger', 'terlambat' => 'danger', 'ditolak' => 'danger',
        'hilang' => 'dark', 'nonaktif' => 'muted', 'dibatalkan' => 'muted', 'offline' => 'muted',
    ];
    $cls = $map[$status ?? ''] ?? 'muted';
    return '<span class="badge badge-' . $cls . '">' . e(label($status)) . '</span>';
}

/** Nilai form: input lama (setelah validasi gagal) atau nilai record. */
function fv(string $key, ?array $row = null, string $default = ''): string
{
    $o = Flash::old($key, null);
    if ($o !== null && !is_array($o)) return (string)$o;
    return isset($row[$key]) ? (string)$row[$key] : $default;
}

function ferr(string $key): string
{
    $m = Flash::error($key);
    return $m ? '<div class="field-error">' . e($m) . '</div>' : '';
}

function sel($a, $b): string
{
    return (string)$a === (string)$b ? ' selected' : '';
}

function clientIp(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/** Link pagination mempertahankan query filter. */
function pagination(array $pg, string $route, array $query = []): string
{
    if (($pg['pages'] ?? 1) <= 1) return '';
    $query = array_filter($query, fn($v) => $v !== '' && $v !== null);
    unset($query['route'], $query['page']);
    $link = function (int $p, string $text, string $cls = '') use ($route, $query) {
        return '<a class="page ' . $cls . '" href="' . e(url($route, $query + ['page' => $p])) . '">' . $text . '</a>';
    };
    $html = '<nav class="pagination" aria-label="Halaman">';
    $cur = $pg['page'];
    $last = $pg['pages'];
    if ($cur > 1) $html .= $link($cur - 1, '&laquo;');
    for ($i = max(1, $cur - 2); $i <= min($last, $cur + 2); $i++) {
        $html .= $link($i, (string)$i, $i === $cur ? 'active' : '');
    }
    if ($cur < $last) $html .= $link($cur + 1, '&raquo;');
    return $html . '<span class="page-info">' . $pg['total'] . ' data</span></nav>';
}

function abort(int $code, string $message = ''): void
{
    $titles = [401 => 'Login diperlukan', 403 => 'Akses ditolak', 404 => 'Halaman tidak ditemukan',
               405 => 'Metode tidak diizinkan', 500 => 'Terjadi kesalahan'];
    $defaults = [404 => 'Halaman yang Anda cari tidak ditemukan.', 500 => 'Terjadi kesalahan. Silakan coba lagi.'];
    if (!headers_sent()) http_response_code($code);
    View::render('errors/error', [
        'code' => $code,
        'title' => $titles[$code] ?? 'Kesalahan',
        'message' => $message !== '' ? $message : ($defaults[$code] ?? 'Permintaan tidak dapat diproses.'),
    ], Auth::check() ? 'main' : 'public');
    exit;
}

/** String kosong -> NULL (untuk kolom nullable). */
function nz($v)
{
    return ($v === null || $v === '') ? null : $v;
}

/** Sertakan potongan view (partial) dengan variabel lokal sendiri. */
function partial(string $name, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require BASE_PATH . '/views/' . $name . '.php';
}
