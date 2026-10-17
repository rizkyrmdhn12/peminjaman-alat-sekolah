<?php
/** Menu sidebar per role: [label, route, prefix route yang dianggap aktif]. Hanya tampilan; akses tetap dicek server. */
return [
    'admin' => [
        ['Dashboard', 'dashboard-admin', ['dashboard']],
        ['Pengguna', 'users', ['users']],
        ['Kategori Alat', 'kategori', ['kategori']],
        ['Data Alat', 'alat', ['alat']],
        ['Peminjaman', 'peminjaman', ['peminjaman']],
        ['Pengembalian', 'pengembalian', ['pengembalian', 'denda']],
        ['Laporan', 'laporan', ['laporan']],
        ['Log Aktivitas', 'log', ['log']],
    ],
    'petugas' => [
        ['Dashboard', 'dashboard-petugas', ['dashboard']],
        ['Peminjaman', 'peminjaman', ['peminjaman']],
        ['Pengembalian', 'pengembalian', ['pengembalian', 'denda']],
        ['Laporan', 'laporan', ['laporan']],
    ],
    'peminjam' => [
        ['Dashboard', 'dashboard-peminjam', ['dashboard']],
        ['Daftar Alat', 'alat', ['alat']],
        ['Ajukan Peminjaman', 'peminjaman-create', ['peminjaman-create', 'peminjaman-store']],
        ['Peminjaman Saya', 'peminjaman-saya', ['peminjaman-saya', 'peminjaman-detail']],
    ],
];
