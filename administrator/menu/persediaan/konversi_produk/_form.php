<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int)($user['id_entitas'] ?? 0);
$isEdit = isset($row) && $row;
$action = $isEdit ? admin_url('index.php?menu=persediaan/konversi-produk/update') : admin_url('index.php?menu=persediaan/konversi-produk/simpan');

$produk = Capsule::table('tb_produk as p')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('p.id_entitas', $id_entitas)
    ->where('p.status_produk', 1)
    ->orderBy('p.nama_produk')
    ->select('p.id_produk','p.nama_produk','p.harga_jual','p.hpp_standar','s.nama_satuan')
    ->get();
$gudang = Capsule::table('tb_gudang')->where('id_entitas',$id_entitas)->where('status_aktif',1)->orderBy('nama_gudang')->get();

$val = function($name, $default='') use ($isEdit, $row) { return $isEdit ? (string)($row->{$name} ?? $default) : $default; };
?>
<div class="card card-app border-0 shadow-sm rounded-4">
  <div class="card-header bg-white border-0">
    <h5 class="mb-1"><?= $isEdit ? 'Edit' : 'Tambah' ?> Konversi Produk</h5>
    <div class="text-muted small">Gunakan untuk pecah stok produk kg menjadi pcs/biji. Nilai HPP sumber akan dipindahkan ke produk tujuan.</div>
  </div>
  <div class="card-body">
    <?php if ($msg = get_flash('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>
    <form method="post" action="<?= esc($action) ?>" class="row g-3">
      <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$row->id_konversi_produk ?>"><?php endif; ?>
      <div class="col-md-4">
        <label class="form-label">Tanggal Konversi <span class="text-danger">*</span></label>
        <input type="date" name="tanggal_konversi" class="form-control" required value="<?= esc(substr($val('tanggal_konversi', date('Y-m-d')),0,10)) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Gudang <span class="text-danger">*</span></label>
        <select name="id_gudang" class="form-select" required>
          <option value="">Pilih gudang</option>
          <?php foreach ($gudang as $g): ?>
            <option value="<?= (int)$g->id_gudang ?>" <?= (int)$val('id_gudang')===(int)$g->id_gudang?'selected':'' ?>><?= esc($g->nama_gudang) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">No Konversi</label>
        <input type="text" class="form-control" value="<?= esc($val('no_konversi_produk','Otomatis')) ?>" disabled>
      </div>

      <div class="col-md-6">
        <label class="form-label">Produk Sumber / Yang Dipecah <span class="text-danger">*</span></label>
        <select name="id_produk_sumber" id="id_produk_sumber" class="form-select" required>
          <option value="">Pilih produk sumber</option>
          <?php foreach ($produk as $p): ?>
            <option value="<?= (int)$p->id_produk ?>" data-satuan="<?= esc($p->nama_satuan ?? '') ?>" <?= (int)$val('id_produk_sumber')===(int)$p->id_produk?'selected':'' ?>><?= esc($p->nama_produk) ?> (<?= esc($p->nama_satuan ?? '-') ?>)</option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Contoh: Dodol 1 Kg.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Produk Tujuan / Hasil Pecah <span class="text-danger">*</span></label>
        <select name="id_produk_tujuan" id="id_produk_tujuan" class="form-select" required>
          <option value="">Pilih produk tujuan</option>
          <?php foreach ($produk as $p): ?>
            <option value="<?= (int)$p->id_produk ?>" data-satuan="<?= esc($p->nama_satuan ?? '') ?>" <?= (int)$val('id_produk_tujuan')===(int)$p->id_produk?'selected':'' ?>><?= esc($p->nama_produk) ?> (<?= esc($p->nama_satuan ?? '-') ?>)</option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Contoh: Dodol Per Biji.</div>
      </div>

      <div class="col-md-3">
        <label class="form-label">Qty Sumber <span class="text-danger">*</span></label>
        <input type="number" step="0.001" min="0.001" name="qty_sumber" id="qty_sumber" class="form-control" required value="<?= esc($val('qty_sumber','')) ?>" placeholder="45">
      </div>
      <div class="col-md-3">
        <label class="form-label">Qty Tujuan <span class="text-danger">*</span></label>
        <input type="number" step="0.001" min="0.001" name="qty_tujuan" id="qty_tujuan" class="form-control" required value="<?= esc($val('qty_tujuan','')) ?>" placeholder="4500">
      </div>
      <div class="col-md-3">
        <label class="form-label">HPP Sumber / Unit</label>
        <input type="text" class="form-control" id="hpp_preview" value="Akan dihitung saat posting" disabled>
      </div>
      <div class="col-md-3">
        <label class="form-label">Estimasi HPP Tujuan</label>
        <input type="text" class="form-control" id="hpp_tujuan_preview" value="-" disabled>
      </div>

      <div class="col-12">
        <label class="form-label">Catatan</label>
        <textarea name="catatan" class="form-control" rows="3" placeholder="Contoh: Pecah Dodol 45 kg menjadi 4.500 pcs untuk penjualan eceran."><?= esc($val('catatan','')) ?></textarea>
      </div>
      <div class="col-12">
        <div class="alert alert-warning small mb-0">
          <b>Pengaruh ke HPP:</b> ketika diposting, stok produk sumber berkurang dan stok produk tujuan bertambah dengan nilai HPP yang sama. Contoh HPP 45 kg = Rp1.350.000, maka 4.500 pcs menerima HPP total Rp1.350.000 atau Rp300/pcs.
        </div>
      </div>
      <div class="col-12 d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="<?= admin_page_url('persediaan/konversi-produk') ?>">Kembali</a>
        <button class="btn btn-primary">Simpan Draft</button>
      </div>
    </form>
  </div>
</div>
<script>
(function(){
  const qs=document.getElementById('qty_sumber'), qt=document.getElementById('qty_tujuan'), out=document.getElementById('hpp_tujuan_preview');
  function hitung(){
    const a=parseFloat(qs.value||0), b=parseFloat(qt.value||0);
    if(a>0 && b>0){ out.value = '1 sumber = ' + (b/a).toLocaleString('id-ID') + ' tujuan'; } else { out.value='-'; }
  }
  qs&&qs.addEventListener('input',hitung); qt&&qt.addEventListener('input',hitung); hitung();
})();
</script>
