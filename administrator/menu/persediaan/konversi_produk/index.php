<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? 'semua'));

if (!function_exists('kp_rupiah')) {
    function kp_rupiah($v): string { return 'Rp ' . number_format((float)$v, 0, ',', '.'); }
}
if (!function_exists('kp_qty')) {
    function kp_qty($v): string { $f=(float)$v; return abs($f-round($f))<0.0001 ? number_format($f,0,',','.') : number_format($f,3,',','.'); }
}

$query = Capsule::table('tb_konversi_produk as k')
    ->leftJoin('tb_produk as ps', 'ps.id_produk', '=', 'k.id_produk_sumber')
    ->leftJoin('tb_produk as pt', 'pt.id_produk', '=', 'k.id_produk_tujuan')
    ->leftJoin('tb_satuan as ss', 'ss.id_satuan', '=', 'ps.id_satuan')
    ->leftJoin('tb_satuan as st', 'st.id_satuan', '=', 'pt.id_satuan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'k.id_gudang')
    ->where('k.id_entitas', $id_entitas)
    ->select('k.*','ps.nama_produk as nama_sumber','pt.nama_produk as nama_tujuan','ss.nama_satuan as satuan_sumber','st.nama_satuan as satuan_tujuan','g.nama_gudang');

if ($q !== '') {
    $query->where(function($w) use ($q) {
        $w->where('k.no_konversi_produk','like','%'.$q.'%')
          ->orWhere('ps.nama_produk','like','%'.$q.'%')
          ->orWhere('pt.nama_produk','like','%'.$q.'%');
    });
}
if (in_array($status, ['draft','posted'], true)) {
    $query->where('k.status_posting', $status);
}

$rows = $query->orderBy('k.id_konversi_produk','desc')->limit(200)->get();
?>
<div class="card card-app border-0 shadow-sm rounded-4">
  <div class="card-header bg-white border-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div>
      <h5 class="mb-1">Konversi Produk</h5>
      <div class="text-muted small">Pecah stok produk kiloan menjadi produk bijian/pcs tanpa merusak HPP.</div>
    </div>
    <a class="btn btn-primary rounded-pill" href="<?= admin_page_url('persediaan/konversi-produk/tambah') ?>">
      <i class="fa fa-plus me-1"></i> Tambah Konversi
    </a>
  </div>
  <div class="card-body">
    <?php if ($msg = get_flash('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = get_flash('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>

    <form class="row g-2 mb-3" method="get">
      <input type="hidden" name="menu" value="persediaan/konversi-produk">
      <div class="col-md-6"><input type="text" name="q" class="form-control" value="<?= esc($q) ?>" placeholder="Cari nomor / produk"></div>
      <div class="col-md-3">
        <select name="status" class="form-select">
          <option value="semua" <?= $status==='semua'?'selected':'' ?>>Semua status</option>
          <option value="draft" <?= $status==='draft'?'selected':'' ?>>Draft</option>
          <option value="posted" <?= $status==='posted'?'selected':'' ?>>Posted</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-outline-primary flex-fill">Terapkan</button>
        <a class="btn btn-outline-secondary" href="<?= admin_page_url('persediaan/konversi-produk') ?>">Reset</a>
      </div>
    </form>

    <div class="alert alert-info small">
      Contoh dodol: hasil produksi masuk sebagai <b>Dodol 1 Kg</b>, lalu konversi 45 kg menjadi 4.500 pcs <b>Dodol Per Biji</b>. Nilai HPP tetap sama, hanya dibagi ke produk tujuan.
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>No</th><th>Tanggal</th><th>Dari Produk</th><th>Ke Produk</th><th class="text-end">Qty Dari</th><th class="text-end">Qty Ke</th><th class="text-end">Total HPP</th><th class="text-end">HPP Satuan Tujuan</th><th>Status</th><th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($rows->isEmpty()): ?>
          <tr><td colspan="10" class="text-center text-muted py-4">Belum ada data konversi produk.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><span class="fw-semibold"><?= esc($r->no_konversi_produk) ?></span></td>
            <td><?= esc(date('d/m/Y', strtotime((string)$r->tanggal_konversi))) ?></td>
            <td><?= esc($r->nama_sumber ?? '-') ?><div class="small text-muted"><?= esc($r->nama_gudang ?? '-') ?></div></td>
            <td><?= esc($r->nama_tujuan ?? '-') ?></td>
            <td class="text-end"><?= kp_qty($r->qty_sumber) ?> <?= esc($r->satuan_sumber ?? '') ?></td>
            <td class="text-end"><?= kp_qty($r->qty_tujuan) ?> <?= esc($r->satuan_tujuan ?? '') ?></td>
            <td class="text-end"><?= kp_rupiah($r->nilai_sumber) ?></td>
            <?php $hppSatuanTujuan = ((float)$r->qty_tujuan > 0) ? ((float)$r->nilai_sumber / (float)$r->qty_tujuan) : 0; ?>
            <td class="text-end"><?= kp_rupiah($hppSatuanTujuan) ?> <span class="text-muted small">/ <?= esc($r->satuan_tujuan ?? 'unit') ?></span></td>
            <td><?= $r->status_posting==='posted' ? '<span class="badge bg-success">Posted</span>' : '<span class="badge bg-secondary">Draft</span>' ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-info" href="<?= admin_page_url('persediaan/konversi-produk/detail') . '&id='.(int)$r->id_konversi_produk ?>">Detail</a>
              <?php if ($r->status_posting === 'draft'): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= admin_page_url('persediaan/konversi-produk/edit') . '&id='.(int)$r->id_konversi_produk ?>">Edit</a>
                <a class="btn btn-sm btn-success" onclick="return confirm('Posting konversi produk ini?')" href="<?= admin_page_url('persediaan/konversi-produk/posting') . '&id='.(int)$r->id_konversi_produk ?>">Posting</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
