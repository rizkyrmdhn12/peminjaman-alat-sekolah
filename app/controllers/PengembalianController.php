<?php
class PengembalianController extends Controller
{
    public function index(): void
    {
        $tab = $this->enumGet('tab', ['aktif', 'riwayat', 'denda']) ?: 'aktif';
        $q = mb_substr($this->get('q'), 0, 50);
        $pm = new PeminjamanModel();
        $pg = new PengembalianModel();
        $f = ['q' => $q, 'status' => '', 'dari' => '', 'sampai' => '', 'jenis' => ''];
        if ($tab === 'aktif') {
            $f['status'] = $this->enumGet('status', PeminjamanModel::AKTIF);
            $data = $pm->paginate($f + ($f['status'] === '' ? ['statuses' => PeminjamanModel::AKTIF] : []), $this->page());
        } elseif ($tab === 'riwayat') {
            $f['dari'] = $this->dateGet('dari');
            $f['sampai'] = $this->dateGet('sampai');
            $data = $pg->riwayat($f, $this->page());
        } else {
            $f['status'] = $this->enumGet('status', ['belum_diselesaikan', 'diselesaikan']);
            $f['jenis'] = $this->enumGet('jenis', ['keterlambatan', 'kerusakan', 'kehilangan']);
            $data = $pg->paginateDenda($f, $this->page());
        }
        $this->render('pengembalian/index', ['title' => 'Pengembalian', 'tab' => $tab, 'f' => $f, 'pg' => $data, 'ringkas' => $pg->dendaBelumSelesai()]);
    }

    public function create(): void
    {
        $id = $this->id();
        SyncService::run(true);
        $pm = new PeminjamanModel();
        $p = $pm->findFor($id, null);
        if (!$p) {
            abort(404, 'Data peminjaman tidak ditemukan.');
        }
        if (!in_array($p['status_peminjaman'], PeminjamanModel::AKTIF, true)) {
            Flash::set('warning', 'Transaksi ini tidak sedang menunggu pengembalian.');
            redirect('peminjaman-detail', ['id' => $id]);
        }
        $pg = new PengembalianModel();
        $now = date('Y-m-d H:i:s');
        $this->render('pengembalian/form', [
            'title' => 'Proses Pengembalian',
            'p' => $p,
            'items' => $pm->details($id),
            'jp' => $p['status_peminjaman'] === 'hilang' ? 0 : $pg->previewJp($id, $now),
            'sekarang' => $now,
            'dendaLama' => $pg->dendaByPeminjaman($id),
        ]);
    }

    public function store(): void
    {
        $id = $this->id('id_peminjaman');
        try {
            SyncService::run(true);
            $pm = new PeminjamanModel();
            $p = $pm->findFor($id, null);
            if (!$p) {
                throw new BusinessException('Data peminjaman tidak ditemukan.');
            }
            if (!in_array($p['status_peminjaman'], PeminjamanModel::AKTIF, true)) {
                throw new BusinessException('Transaksi ini tidak sedang menunggu pengembalian.');
            }
            $allowed = ['baik', 'rusak_ringan', 'rusak_berat'];
            if ($p['status_peminjaman'] !== 'hilang') {
                $allowed[] = 'hilang';
            }
            $kond = is_array($_POST['kondisi'] ?? null) ? $_POST['kondisi'] : [];
            $cat = is_array($_POST['catatan_item'] ?? null) ? $_POST['catatan_item'] : [];
            $items = $pm->details($id);
            $map = [];
            $kode = [];
            foreach ($items as $it) {
                $d = (int)$it['id_detail_peminjaman'];
                $k = $kond[$d] ?? '';
                if (!is_string($k) || !in_array($k, $allowed, true)) {
                    throw new BusinessException('Kondisi setiap alat wajib dipilih.');
                }
                $c = isset($cat[$d]) && is_string($cat[$d]) ? mb_substr(trim($cat[$d]), 0, 255) : '';
                $map[$d] = ['kondisi' => $k, 'catatan' => $c];
                $kode[$d] = $it['kode_inventaris'];
            }
            $pgm = new PengembalianModel();
            $idPg = $pgm->proses($id, Auth::id(), $map, nz(mb_substr($this->post('catatan'), 0, 500)), date('Y-m-d H:i:s'));

            $this->log('RETURN', 'pengembalian', $idPg, 'pengembalian ' . $p['kode_peminjaman']);
            foreach ($map as $d => $m) {
                if ($m['kondisi'] === 'rusak_ringan' || $m['kondisi'] === 'rusak_berat') {
                    $this->log('DAMAGE', 'pengembalian', $idPg, $kode[$d] . ' ' . str_replace('_', ' ', $m['kondisi']));
                } elseif ($m['kondisi'] === 'hilang') {
                    $this->log('LOST', 'pengembalian', $idPg, $kode[$d] . ' dilaporkan hilang');
                }
            }
            $rec = $pgm->find($idPg);
            $total = $rec ? (float)$rec['total_denda'] : 0.0;
            if ($total > 0) {
                $this->log('FINE', 'pengembalian', $idPg, 'total denda ' . rupiah($total));
            }
            Flash::set('success', 'Pengembalian berhasil dicatat.' . ($total > 0 ? ' Total denda: ' . rupiah($total) . '.' : ''));
            redirect('pengembalian-detail', ['id' => $idPg]);
        } catch (Throwable $e) {
            $this->fail($e, 'pengembalian-create', ['id' => $id]);
        }
    }

    public function detail(): void
    {
        $pgm = new PengembalianModel();
        $r = $pgm->find($this->id());
        if (!$r) {
            abort(404, 'Data pengembalian tidak ditemukan.');
        }
        $this->render('pengembalian/detail', [
            'title' => 'Pengembalian ' . $r['kode_peminjaman'],
            'r' => $r,
            'items' => $pgm->items((int)$r['id_pengembalian']),
            'denda' => $pgm->dendaByPeminjaman((int)$r['id_peminjaman']),
        ]);
    }

    public function edit(): void
    {
        $r = (new PengembalianModel())->find($this->id());
        if (!$r) {
            abort(404, 'Data pengembalian tidak ditemukan.');
        }
        $this->render('pengembalian/edit', ['title' => 'Ubah Catatan Pengembalian', 'r' => $r]);
    }

    public function update(): void
    {
        $id = $this->id();
        $pgm = new PengembalianModel();
        if (!$pgm->find($id)) {
            abort(404, 'Data pengembalian tidak ditemukan.');
        }
        try {
            $pgm->updateCatatan($id, nz(mb_substr($this->post('catatan'), 0, 500)));
            $this->log('UPDATE', 'pengembalian', $id, 'mengubah catatan pengembalian');
            Flash::set('success', 'Data berhasil disimpan.');
        } catch (Throwable $e) {
            $this->fail($e, 'pengembalian-edit', ['id' => $id]);
        }
        redirect('pengembalian-detail', ['id' => $id]);
    }

    public function delete(): void
    {
        $id = $this->id();
        try {
            $idPeminjaman = (new PengembalianModel())->batalkanTanpaDenda($id);
            $this->log('DELETE', 'pengembalian', $id, 'membatalkan pengembalian (koreksi), transaksi #' . $idPeminjaman . ' kembali dipinjam');
            Flash::set('success', 'Pengembalian dibatalkan. Transaksi kembali berstatus dipinjam.');
        } catch (Throwable $e) {
            $this->fail($e, 'pengembalian-detail', ['id' => $id]);
        }
        redirect('pengembalian', ['tab' => 'riwayat']);
    }

    public function dendaSelesai(): void
    {
        $id = $this->id('id_denda');
        try {
            $pgm = new PengembalianModel();
            $d = $pgm->dendaInfo($id);
            if (!$d || !$pgm->selesaikanDenda($id)) {
                throw new BusinessException('Denda tidak ditemukan atau sudah diselesaikan.');
            }
            $this->log('FINE', 'denda', $id, 'denda ' . $d['jenis_denda'] . ' ' . rupiah($d['jumlah_denda']) . ' diselesaikan');
            Flash::set('success', 'Denda ditandai selesai.');
        } catch (Throwable $e) {
            $this->fail($e, 'pengembalian', ['tab' => 'denda']);
        }
        redirect('pengembalian', ['tab' => 'denda']);
    }
}
