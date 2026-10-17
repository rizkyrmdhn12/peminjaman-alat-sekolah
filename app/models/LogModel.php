<?php
class LogModel extends Model
{
    public function add(?int $idUser, string $aksi, string $modul, ?int $ref, string $ket = ''): void
    {
        $text = $ket !== '' ? $aksi . ': ' . $ket : $aksi;
        $ip = clientIp();
        $this->exec('INSERT INTO log_aktivitas (id_user, aktivitas, modul, id_referensi, ip_address) VALUES (?,?,?,?,?)', [
            $idUser, mb_substr($text, 0, 255), $modul, $ref, $ip !== '' ? $ip : null,
        ]);
    }

    private const SELECT = 'l.id_log, l.aktivitas, l.modul, l.id_referensi, l.ip_address, l.created_at, u.username, u.nama_lengkap';
    private const FROM = 'log_aktivitas l LEFT JOIN users u ON u.id_user = l.id_user';

    public function paginate(array $f, int $page): array
    {
        $where = [];
        $par = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = '(l.aktivitas LIKE ? OR u.username LIKE ? OR u.nama_lengkap LIKE ?)';
            $l = $this->like($f['q']);
            array_push($par, $l, $l, $l);
        }
        if (($f['modul'] ?? '') !== '') {
            $where[] = 'l.modul = ?';
            $par[] = $f['modul'];
        }
        if (($f['aksi'] ?? '') !== '') {
            $where[] = 'l.aktivitas LIKE ?';
            $par[] = $f['aksi'] . '%';
        }
        if (($f['dari'] ?? '') !== '') {
            $where[] = 'l.created_at >= ?';
            $par[] = $f['dari'] . ' 00:00:00';
        }
        if (($f['sampai'] ?? '') !== '') {
            $where[] = 'l.created_at < ?';
            $par[] = date('Y-m-d', strtotime($f['sampai'] . ' +1 day')) . ' 00:00:00';
        }
        return $this->paged(self::SELECT, self::FROM, $where, $par, 'l.id_log DESC', $page, 15);
    }

    public function recent(int $limit): array
    {
        return $this->all('SELECT ' . self::SELECT . ' FROM ' . self::FROM . ' ORDER BY l.id_log DESC LIMIT ?', [$limit]);
    }

    public function moduls(): array
    {
        return array_column($this->all('SELECT DISTINCT modul FROM log_aktivitas ORDER BY modul'), 'modul');
    }

    /** Percobaan login gagal dari IP tertentu (pembatas brute force). */
    public function loginGagal(string $ip, int $menit): int
    {
        return (int)$this->val("SELECT COUNT(*) FROM log_aktivitas WHERE modul = 'auth' AND aktivitas LIKE 'LOGIN_GAGAL%' AND ip_address = ? AND created_at >= ?", [$ip, date('Y-m-d H:i:s', time() - $menit * 60)]);
    }
}
