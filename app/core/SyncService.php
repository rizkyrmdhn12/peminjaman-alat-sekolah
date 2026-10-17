<?php
/**
 * Fallback jika MySQL Event Scheduler tidak aktif: memanggil procedure yang sama dengan event.
 * (pengajuan kedaluwarsa -> dibatalkan, lewat batas -> terlambat, lewat akhir KBM -> hilang + denda)
 * Dipanggil maksimal 1x per SYNC_INTERVAL detik per sesi, dan dipaksa sebelum proses kritis.
 */
final class SyncService
{
    public static function run(bool $force = false): void
    {
        if (!$force && (int)($_SESSION['_sync_at'] ?? 0) > time() - SYNC_INTERVAL) {
            return;
        }
        $_SESSION['_sync_at'] = time();
        try {
            $st = Database::pdo()->prepare('CALL sp_sinkron_status_peminjaman(?)');
            $st->execute([date('Y-m-d H:i:s')]);
            $st->closeCursor();
        } catch (Throwable $e) {
            AppLog::error($e, 'sinkronisasi status peminjaman gagal');
        }
    }
}
