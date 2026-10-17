<?php
/** Permission matrix (RBAC). Role diambil dari session/database, tidak pernah dari input pengguna. */
return [
    'admin' => [
        'dashboard.admin',
        'user.view', 'user.create', 'user.update', 'user.delete',
        'kategori.view', 'kategori.create', 'kategori.update', 'kategori.delete',
        'alat.view', 'alat.create', 'alat.update', 'alat.delete',
        'peminjaman.view', 'peminjaman.offline', 'peminjaman.approve', 'peminjaman.reject',
        'peminjaman.update', 'peminjaman.delete',
        'pengembalian.view', 'pengembalian.create', 'pengembalian.update', 'pengembalian.delete',
        'denda.update',
        'laporan.view', 'laporan.print',
        'log.view',
    ],
    'petugas' => [
        'dashboard.petugas',
        'peminjaman.view', 'peminjaman.offline', 'peminjaman.approve', 'peminjaman.reject',
        'pengembalian.view', 'pengembalian.create',
        'denda.update',
        'laporan.view', 'laporan.print',
    ],
    'peminjam' => [
        'dashboard.peminjam',
        'alat.view',
        'peminjaman.create', 'peminjaman.own', 'peminjaman.cancel_own',
    ],
];
