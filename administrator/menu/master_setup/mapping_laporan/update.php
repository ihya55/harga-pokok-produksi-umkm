<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
require_once __DIR__ . '/helpers_mapping_laporan.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/mapping_laporan');
}

$id_entitas = mapping_laporan_id_entitas();
$id_pengguna = mapping_laporan_id_pengguna();
$tab = strtoupper(trim((string) ($_POST['tab'] ?? 'NERACA')));
$q = trim((string) ($_POST['q'] ?? ''));
$mapping = $_POST['mapping'] ?? [];
$detail = $_POST['detail'] ?? [];
$newDetail = $_POST['new_detail'] ?? [];

$redirectParams = ['menu' => 'master_setup/mapping_laporan'];
if ($tab !== '') {
    $redirectParams['tab'] = $tab;
}
if ($q !== '') {
    $redirectParams['q'] = $q;
}
$redirectUrl = admin_url('index.php?' . http_build_query($redirectParams));

$validArah = array_keys(mapping_laporan_arah_saldo_options());
$validOperator = array_keys(mapping_laporan_operator_options());

try {
    Capsule::connection()->transaction(function () use ($id_entitas, $id_pengguna, $mapping, $detail, $newDetail, $validArah, $validOperator) {
        if (is_array($mapping)) {
            foreach ($mapping as $idMappingRaw => $data) {
                $idMapping = (int) $idMappingRaw;
                if (!is_array($data)) {
                    continue;
                }

                $row = Capsule::table('tb_mapping_laporan')
                    ->where('id_entitas', $id_entitas)
                    ->where('id_mapping_laporan', $idMapping)
                    ->first();

                if (!$row) {
                    continue;
                }

                $update = [
                    'kategori_label' => trim((string) ($data['kategori_label'] ?? $row->kategori_label)),
                    'sub_label' => trim((string) ($data['sub_label'] ?? $row->sub_label)),
                    'tanggal_diubah' => date('Y-m-d H:i:s'),
                    'diubah_oleh' => $id_pengguna > 0 ? $id_pengguna : null,
                ];

                if ((string) $row->tipe_baris !== 'DETAIL') {
                    $update['rumus_total'] = trim((string) ($data['rumus_total'] ?? $row->rumus_total));
                }

                if ($update['kategori_label'] === '' || $update['sub_label'] === '') {
                    throw new RuntimeException('Label kategori dan label baris laporan wajib diisi.');
                }

                Capsule::table('tb_mapping_laporan')
                    ->where('id_entitas', $id_entitas)
                    ->where('id_mapping_laporan', $idMapping)
                    ->update($update);
            }
        }

        if (is_array($detail)) {
            foreach ($detail as $idDetailRaw => $data) {
                $idDetail = (int) $idDetailRaw;
                if (!is_array($data)) {
                    continue;
                }

                $row = Capsule::table('tb_mapping_laporan_detail')
                    ->where('id_entitas', $id_entitas)
                    ->where('id_mapping_laporan_detail', $idDetail)
                    ->first();

                if (!$row) {
                    continue;
                }

                if (!empty($data['hapus'])) {
                    Capsule::table('tb_mapping_laporan_detail')
                        ->where('id_entitas', $id_entitas)
                        ->where('id_mapping_laporan_detail', $idDetail)
                        ->delete();
                    continue;
                }

                $arah = strtoupper(trim((string) ($data['arah_saldo'] ?? 'NORMAL')));
                $operator = strtoupper(trim((string) ($data['operator_nilai'] ?? 'TAMBAH')));

                if (!in_array($arah, $validArah, true)) {
                    throw new RuntimeException('Arah saldo mapping laporan tidak valid.');
                }
                if (!in_array($operator, $validOperator, true)) {
                    throw new RuntimeException('Operator nilai mapping laporan tidak valid.');
                }

                [$tipe, $nilai, $idCoa] = mapping_laporan_validate_source(
                    (string) ($data['tipe_sumber'] ?? ''),
                    (string) ($data['nilai_sumber'] ?? ''),
                    $id_entitas
                );

                Capsule::table('tb_mapping_laporan_detail')
                    ->where('id_entitas', $id_entitas)
                    ->where('id_mapping_laporan_detail', $idDetail)
                    ->update([
                        'tipe_sumber' => $tipe,
                        'nilai_sumber' => $nilai,
                        'id_coa' => $idCoa,
                        'arah_saldo' => $arah,
                        'operator_nilai' => $operator,
                        'keterangan' => trim((string) ($data['keterangan'] ?? '')),
                        'status_aktif' => !empty($data['status_aktif']) ? 1 : 0,
                        'tanggal_diubah' => date('Y-m-d H:i:s'),
                        'diubah_oleh' => $id_pengguna > 0 ? $id_pengguna : null,
                    ]);
            }
        }

        if (is_array($newDetail)) {
            foreach ($newDetail as $idMappingRaw => $newRowsRaw) {
                $idMapping = (int) $idMappingRaw;
                if (!is_array($newRowsRaw)) {
                    continue;
                }

                $mappingRow = Capsule::table('tb_mapping_laporan')
                    ->where('id_entitas', $id_entitas)
                    ->where('id_mapping_laporan', $idMapping)
                    ->where('tipe_baris', 'DETAIL')
                    ->first();

                if (!$mappingRow) {
                    continue;
                }

                $newRows = [];
                if (array_key_exists('nilai_sumber', $newRowsRaw)) {
                    // Kompatibel dengan format lama: new_detail[id_mapping][nilai_sumber]
                    $newRows[] = $newRowsRaw;
                } else {
                    // Format baru: new_detail[id_mapping][row_key][nilai_sumber]
                    foreach ($newRowsRaw as $rowData) {
                        if (is_array($rowData)) {
                            $newRows[] = $rowData;
                        }
                    }
                }

                foreach ($newRows as $data) {
                    if (!is_array($data)) {
                        continue;
                    }

                    $nilaiBaru = trim((string) ($data['nilai_sumber'] ?? ''));
                    if ($nilaiBaru === '') {
                        continue;
                    }

                    $arah = strtoupper(trim((string) ($data['arah_saldo'] ?? 'DEBIT_MINUS_KREDIT')));
                    $operator = strtoupper(trim((string) ($data['operator_nilai'] ?? 'TAMBAH')));

                    if (!in_array($arah, $validArah, true)) {
                        throw new RuntimeException('Arah saldo baris baru tidak valid.');
                    }
                    if (!in_array($operator, $validOperator, true)) {
                        throw new RuntimeException('Operator nilai baris baru tidak valid.');
                    }

                    [$tipe, $nilai, $idCoa] = mapping_laporan_validate_source(
                        (string) ($data['tipe_sumber'] ?? 'COA'),
                        $nilaiBaru,
                        $id_entitas
                    );

                    $exists = Capsule::table('tb_mapping_laporan_detail')
                        ->where('id_entitas', $id_entitas)
                        ->where('id_mapping_laporan', $idMapping)
                        ->where('tipe_sumber', $tipe)
                        ->where('nilai_sumber', $nilai)
                        ->where(function ($q) use ($idCoa) {
                            if ($idCoa === null) {
                                $q->whereNull('id_coa');
                            } else {
                                $q->where('id_coa', $idCoa);
                            }
                        })
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    Capsule::table('tb_mapping_laporan_detail')->insert([
                        'id_mapping_laporan' => $idMapping,
                        'id_entitas' => $id_entitas,
                        'tipe_sumber' => $tipe,
                        'nilai_sumber' => $nilai,
                        'id_coa' => $idCoa,
                        'arah_saldo' => $arah,
                        'operator_nilai' => $operator,
                        'keterangan' => trim((string) ($data['keterangan'] ?? '')),
                        'status_aktif' => 1,
                        'tanggal_dibuat' => date('Y-m-d H:i:s'),
                        'dibuat_oleh' => $id_pengguna > 0 ? $id_pengguna : null,
                        'tanggal_diubah' => null,
                        'diubah_oleh' => null,
                    ]);
                }
            }
        }
    });

    set_flash('success', 'Mapping laporan berhasil diperbarui.');
    redirect_url($redirectUrl);
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    redirect_url($redirectUrl);
}
