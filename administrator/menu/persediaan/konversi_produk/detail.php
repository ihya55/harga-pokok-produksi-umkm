<?php
declare(strict_types=1);
use Illuminate\Database\Capsule\Manager as Capsule;
$id_entitas = (int)($user['id_entitas'] ?? 0);
$id = (int)($_GET['id'] ?? 0);
$r = Capsule::table('tb_konversi_produk as k')
    ->leftJoin('tb_produk as ps','ps.id_produk','=','k.id_produk_sumber')
    ->leftJoin('tb_produk as pt','pt.id_produk','=','k.id_produk_tujuan')
    ->leftJoin('tb_satuan as ss','ss.id_satuan','=','ps.id_satuan')
    ->leftJoin('tb_satuan as st','st.id_satuan','=','pt.id_satuan')
    ->leftJoin('tb_gudang as g','g.id_gudang','=','k.id_gudang')
    ->where('k.id_entitas',$id_entitas)->where('k.id_konversi_produk',$id)
    ->select('k.*','ps.nama_produk as nama_sumber','pt.nama_produk as nama_tujuan','ss.nama_satuan as satuan_sumber','st.nama_satuan as satuan_tujuan','g.nama_gudang')->first();
if (!$r) { set_flash('error','Data konversi produk tidak ditemukan.'); redirect_admin('persediaan/konversi-produk'); }
$fmt=function($v){$f=(float)$v; return abs($f-round($f))<0.0001?number_format($f,0,',','.'):number_format($f,3,',','.');};
$rp=function($v){return 'Rp '.number_format((float)$v,0,',','.');};
?>
<div class="card card-app border-0 shadow-sm rounded-4">
  <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
    <div><h5 class="mb-1">Detail Konversi Produk</h5><div class="text-muted small"><?= esc($r->no_konversi_produk) ?></div></div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="<?= admin_page_url('persediaan/konversi-produk') ?>">Kembali</a>
      <?php if ($r->status_posting==='draft'): ?><a class="btn btn-success" onclick="return confirm('Posting konversi produk ini?')" href="<?= admin_page_url('persediaan/konversi-produk/posting').'&id='.(int)$r->id_konversi_produk ?>">Posting</a><?php endif; ?>
    </div>
  </div>
  <div class="card-body">
    <?php if ($msg = get_flash('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = get_flash('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>
    <div class="row g-3">
      <div class="col-md-3"><div class="border rounded-3 p-3"><div class="text-muted small">Tanggal</div><div class="fw-semibold"><?= esc(date('d/m/Y',strtotime((string)$r->tanggal_konversi))) ?></div></div></div>
      <div class="col-md-3"><div class="border rounded-3 p-3"><div class="text-muted small">Gudang</div><div class="fw-semibold"><?= esc($r->nama_gudang ?? '-') ?></div></div></div>
      <div class="col-md-3"><div class="border rounded-3 p-3"><div class="text-muted small">Status</div><div><?= $r->status_posting==='posted'?'<span class="badge bg-success">Posted</span>':'<span class="badge bg-secondary">Draft</span>' ?></div></div></div>
      <div class="col-md-3"><div class="border rounded-3 p-3"><div class="text-muted small">Total HPP</div><div class="fw-semibold"><?= $rp($r->nilai_sumber) ?></div></div></div>
    </div>
    <div class="table-responsive mt-3"><table class="table table-bordered align-middle"><thead class="table-light"><tr><th>Keterangan</th><th>Produk</th><th class="text-end">Qty</th><th class="text-end">HPP/Unit</th><th class="text-end">Nilai</th></tr></thead><tbody>
      <tr><td>Keluar</td><td><?= esc($r->nama_sumber) ?></td><td class="text-end"><?= $fmt($r->qty_sumber).' '.esc($r->satuan_sumber ?? '') ?></td><td class="text-end"><?= $rp($r->hpp_sumber) ?></td><td class="text-end"><?= $rp($r->nilai_sumber) ?></td></tr>
      <tr><td>Masuk</td><td><?= esc($r->nama_tujuan) ?></td><td class="text-end"><?= $fmt($r->qty_tujuan).' '.esc($r->satuan_tujuan ?? '') ?></td><td class="text-end"><?= $rp($r->hpp_tujuan) ?></td><td class="text-end"><?= $rp($r->nilai_tujuan) ?></td></tr>
    </tbody></table></div>
    <div class="mt-3"><div class="text-muted small">Catatan</div><div><?= nl2br(esc($r->catatan ?? '-')) ?></div></div>
  </div>
</div>
