<?php
declare(strict_types=1);

function ambil_menu_role_flat(int $id_role): array
{
    $rows = RoleMenuORM::query()
        ->from('tb_role_menu as rm')
        ->join('tb_menu as m', 'm.id_menu', '=', 'rm.id_menu')
        ->where('rm.id_role', $id_role)
        ->where('rm.status_aktif', 1)
        ->where('rm.boleh_lihat', 1)
        ->where('m.status_aktif', 1)
        ->orderBy('m.urutan', 'asc')
        ->get([
            'm.id_menu',
            'm.id_menu_induk',
            'm.kode_menu',
            'm.nama_menu',
            'm.jenis_menu',
            'm.url',
            'm.ikon',
            'm.urutan',
        ]);

    return $rows->map(function ($row) {
        return [
            'id_menu'       => (int) $row->id_menu,
            'id_menu_induk' => $row->id_menu_induk ? (int) $row->id_menu_induk : null,
            'kode_menu'     => (string) $row->kode_menu,
            'nama_menu'     => (string) $row->nama_menu,
            'jenis_menu'    => (string) $row->jenis_menu,
            'url'           => (string) $row->url,
            'ikon'          => (string) $row->ikon,
            'urutan'        => (int) $row->urutan,
        ];
    })->toArray();
}

function ambil_menu_role_tree(int $id_role): array
{
    $flat = ambil_menu_role_flat($id_role);

    $indexed = [];
    foreach ($flat as $row) {
        $row['children'] = [];
        $indexed[$row['id_menu']] = $row;
    }

    $tree = [];
    foreach ($indexed as $id => &$item) {
        if (!empty($item['id_menu_induk']) && isset($indexed[$item['id_menu_induk']])) {
            $indexed[$item['id_menu_induk']]['children'][] = &$item;
        } else {
            $tree[] = &$item;
        }
    }

    return $tree;
}

function boleh_akses_menu(string $menu): bool
{
    $user = user_login();
    if (!$user) {
        return false;
    }

    $menu = trim($menu, '/');

    if ($menu === trim(halaman_awal_role(), '/')) {
        return true;
    }

    if (in_array($menu, ['profil', 'ganti-password'], true)) {
        return true;
    }

    $list = ambil_menu_role_flat((int) $user['id_role']);

    foreach ($list as $item) {
        $url = trim((string) $item['url'], '/');

        if ($url === '') {
            continue;
        }

        if ($menu === $url || str_starts_with($menu, $url . '/')) {
            return true;
        }
    }

    return false;
}