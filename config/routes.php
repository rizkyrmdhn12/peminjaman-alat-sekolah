<?php
/**
 * Tabel route: nama => [Controller, method, permission, HTTP method]
 * permission: null = publik | 'auth' = cukup login | 'x.y' = permission | ['a','b'] = salah satu
 * Semua route non-publik melewati Auth::requireLogin()/requirePermission() di index.php.
 */
return [
    'home'      => ['LandingController', 'index', null, 'GET'],
    'login'     => ['AuthController', 'login', null, 'ANY'],
    'register'  => ['AuthController', 'register', null, 'ANY'],
    'logout'    => ['AuthController', 'logout', 'auth', 'POST'],

    'dashboard'          => ['DashboardController', 'index', 'auth', 'GET'],
    'dashboard-admin'    => ['DashboardController', 'admin', 'dashboard.admin', 'GET'],
    'dashboard-petugas'  => ['DashboardController', 'petugas', 'dashboard.petugas', 'GET'],
    'dashboard-peminjam' => ['DashboardController', 'peminjam', 'dashboard.peminjam', 'GET'],

    'users'        => ['UserController', 'index', 'user.view', 'GET'],
    'users-create' => ['UserController', 'create', 'user.create', 'GET'],
    'users-store'  => ['UserController', 'store', 'user.create', 'POST'],
    'users-edit'   => ['UserController', 'edit', 'user.update', 'GET'],
    'users-update' => ['UserController', 'update', 'user.update', 'POST'],
    'users-delete' => ['UserController', 'delete', 'user.delete', 'POST'],

    'kategori'        => ['KategoriController', 'index', 'kategori.view', 'GET'],
    'kategori-create' => ['KategoriController', 'create', 'kategori.create', 'GET'],
    'kategori-store'  => ['KategoriController', 'store', 'kategori.create', 'POST'],
    'kategori-edit'   => ['KategoriController', 'edit', 'kategori.update', 'GET'],
    'kategori-update' => ['KategoriController', 'update', 'kategori.update', 'POST'],
    'kategori-delete' => ['KategoriController', 'delete', 'kategori.delete', 'POST'],

    'alat'        => ['AlatController', 'index', 'alat.view', 'GET'],
    'alat-show'   => ['AlatController', 'show', 'alat.view', 'GET'],
    'alat-create' => ['AlatController', 'create', 'alat.create', 'GET'],
    'alat-store'  => ['AlatController', 'store', 'alat.create', 'POST'],
    'alat-edit'   => ['AlatController', 'edit', 'alat.update', 'GET'],
    'alat-update' => ['AlatController', 'update', 'alat.update', 'POST'],
    'alat-delete' => ['AlatController', 'delete', 'alat.delete', 'POST'],

    'peminjaman'               => ['PeminjamanController', 'index', 'peminjaman.view', 'GET'],
    'peminjaman-saya'          => ['PeminjamanController', 'saya', 'peminjaman.own', 'GET'],
    'peminjaman-detail'        => ['PeminjamanController', 'detail', ['peminjaman.view', 'peminjaman.own'], 'GET'],
    'peminjaman-create'        => ['PeminjamanController', 'create', 'peminjaman.create', 'GET'],
    'peminjaman-store'         => ['PeminjamanController', 'store', 'peminjaman.create', 'POST'],
    'peminjaman-cancel-own'    => ['PeminjamanController', 'cancelOwn', 'peminjaman.cancel_own', 'POST'],
    'peminjaman-offline'       => ['PeminjamanController', 'offline', 'peminjaman.offline', 'GET'],
    'peminjaman-offline-store' => ['PeminjamanController', 'offlineStore', 'peminjaman.offline', 'POST'],
    'peminjaman-approve'       => ['PeminjamanController', 'approve', 'peminjaman.approve', 'POST'],
    'peminjaman-reject'        => ['PeminjamanController', 'reject', 'peminjaman.reject', 'POST'],
    'peminjaman-edit'          => ['PeminjamanController', 'edit', 'peminjaman.update', 'GET'],
    'peminjaman-update'        => ['PeminjamanController', 'update', 'peminjaman.update', 'POST'],
    'peminjaman-cancel'        => ['PeminjamanController', 'cancel', 'peminjaman.update', 'POST'],
    'peminjaman-delete'        => ['PeminjamanController', 'delete', 'peminjaman.delete', 'POST'],

    'pengembalian'        => ['PengembalianController', 'index', 'pengembalian.view', 'GET'],
    'pengembalian-create' => ['PengembalianController', 'create', 'pengembalian.create', 'GET'],
    'pengembalian-store'  => ['PengembalianController', 'store', 'pengembalian.create', 'POST'],
    'pengembalian-detail' => ['PengembalianController', 'detail', 'pengembalian.view', 'GET'],
    'pengembalian-edit'   => ['PengembalianController', 'edit', 'pengembalian.update', 'GET'],
    'pengembalian-update' => ['PengembalianController', 'update', 'pengembalian.update', 'POST'],
    'pengembalian-delete' => ['PengembalianController', 'delete', 'pengembalian.delete', 'POST'],
    'denda-selesai'       => ['PengembalianController', 'dendaSelesai', 'denda.update', 'POST'],

    'laporan'       => ['LaporanController', 'index', 'laporan.view', 'GET'],
    'laporan-cetak' => ['LaporanController', 'cetak', 'laporan.print', 'GET'],

    'log' => ['LogController', 'index', 'log.view', 'GET'],
];
