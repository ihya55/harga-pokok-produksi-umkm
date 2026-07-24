<?php
declare(strict_types=1);
use Illuminate\Database\Capsule\Manager as Capsule;
$id_entitas = (int)($user['id_entitas'] ?? 0);
$id = (int)($_GET['id'] ?? 0);
$row = Capsule::table('tb_konversi_produk')->where('id_entitas',$id_entitas)->where('id_konversi_produk',$id)->first();
if (!$row) { set_flash('error','Data konversi produk tidak ditemukan.'); redirect_admin('persediaan/konversi-produk'); }
if ($row->status_posting !== 'draft') { set_flash('error','Data posted tidak bisa diedit.'); redirect_admin('persediaan/konversi-produk'); }
require __DIR__ . '/_form.php';
