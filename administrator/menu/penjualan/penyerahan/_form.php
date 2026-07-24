<?php
$pesanan_options = $pesanan_options ?? [];
$gudang_options = $gudang_options ?? [];
$produk_options = $produk_options ?? [];
$detail_rows = $detail_rows ?? [];

$pesanan_js = [];
foreach ($pesanan_options as $p) {
    $pesanan_js[] = [
        'id_pesanan_penjualan' => (int) $p->id_pesanan_penjualan,
        'id_pelanggan' => (int) $p->id_pelanggan,
        'pelanggan_label' => (string) (($p->kode_pelanggan ?? '-') . ' - ' . ($p->nama_pelanggan ?? '-')),
        'alamat_pelanggan' => (string) ($p->alamat_pelanggan ?? ''),
    ];
}

$produk_js = [];
foreach ($produk_options as $p) {
    $produk_js[] = [
        'id_produk' => (int) $p->id_produk,
        'kode_produk' => (string) ($p->kode_produk ?? ''),
        'nama_produk' => (string) ($p->nama_produk ?? ''),
        'nama_satuan' => (string) ($p->nama_satuan ?? ''),
        'qty_saldo' => (float) ($p->qty_saldo ?? 0),
        'qty_pesanan' => (float) ($p->qty_pesanan ?? 0),
        'hpp_rata_rata' => (float) ($p->hpp_rata_rata ?? 0),
        'label' => trim((string) (($p->kode_produk ?? '-') . ' - ' . ($p->nama_produk ?? '-') . ' - Stok: ' . rtrim(rtrim(number_format((float) ($p->qty_saldo ?? 0), 3, '.', ''), '0'), '.'))),
    ];
}
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Penyerahan Penjualan') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="form-penyerahan-penjualan">
            <?php if (!empty($data_form['id_penyerahan_penjualan'])): ?>
                <input type="hidden" name="id_penyerahan_penjualan" value="<?= (int) $data_form['id_penyerahan_penjualan'] ?>">
            <?php endif; ?>

            <input type="hidden" name="id_pelanggan" id="id-pelanggan" value="<?= esc($data_form['id_pelanggan'] ?? '') ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Penyerahan</label>
                    <input type="text" class="form-control" value="<?= esc($data_form['no_penyerahan_penjualan'] ?? 'Otomatis saat disimpan') ?>" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal Penyerahan <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_penyerahan" class="form-control" required value="<?= esc($data_form['tanggal_penyerahan'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <input type="text" class="form-control" value="<?= esc(ucfirst((string) ($data_form['status_penyerahan'] ?? 'draft'))) ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Pesanan Penjualan <span class="text-danger">*</span></label>
                    <select name="id_pesanan_penjualan" id="id-pesanan-penjualan" class="form-select" required>
                        <option value="">- Pilih Pesanan Penjualan -</option>
                        <?php foreach ($pesanan_options as $p): ?>
                            <option
                                value="<?= (int) $p->id_pesanan_penjualan ?>"
                                data-id-pelanggan="<?= (int) $p->id_pelanggan ?>"
                                data-pelanggan="<?= esc(($p->kode_pelanggan ?? '-') . ' - ' . ($p->nama_pelanggan ?? '-')) ?>"
                                data-alamat-pelanggan="<?= esc((string) ($p->alamat_pelanggan ?? '')) ?>"
                                <?= ((string) ($data_form['id_pesanan_penjualan'] ?? '') === (string) $p->id_pesanan_penjualan) ? 'selected' : '' ?>>
                                <?= esc(($p->no_pesanan_penjualan ?? '-') . ' - ' . ($p->kode_pelanggan ?? '-') . ' - ' . ($p->nama_pelanggan ?? '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Penyerahan wajib berasal dari pesanan penjualan terkonfirmasi.</div><button type="button" class="btn btn-sm btn-outline-success mt-2" onclick="bukaScanPesananOnline()"><i class="bi bi-upc-scan me-1"></i>Ambil dari Barcode Pesanan Online</button>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Pelanggan</label>
                    <input type="text" class="form-control" id="pelanggan-display" value="<?= esc($data_form['pelanggan_label'] ?? '') ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Gudang <span class="text-danger">*</span></label>
                    <select name="id_gudang" id="id-gudang" class="form-select" required>
                        <option value="">- Pilih Gudang -</option>
                        <?php foreach ($gudang_options as $g): ?>
                            <option
                                value="<?= (int) $g->id_gudang ?>"
                                <?= ((string) ($data_form['id_gudang'] ?? '') === (string) $g->id_gudang) ? 'selected' : '' ?>>
                                <?= esc(($g->kode_gudang ?? '-') . ' - ' . ($g->nama_gudang ?? '-') . ' - ' . ($g->jenis_gudang ?? '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Produk hanya dimuat jika saldo stok produk di gudang ini lebih dari 0.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Alamat Tujuan <span class="text-danger">*</span></label>
                    <select name="jenis_alamat_tujuan" id="jenis-alamat-tujuan" class="form-select" required onchange="aturAlamatTujuan()">
                        <option value="pelanggan" <?= ($data_form['jenis_alamat_tujuan'] ?? 'pelanggan') === 'pelanggan' ? 'selected' : '' ?>>
                            Alamat pelanggan
                        </option>
                        <option value="toko" <?= ($data_form['jenis_alamat_tujuan'] ?? '') === 'toko' ? 'selected' : '' ?>>
                            Di tempat / toko
                        </option>
                        <option value="lain" <?= ($data_form['jenis_alamat_tujuan'] ?? '') === 'lain' ? 'selected' : '' ?>>
                            Alamat lain
                        </option>
                    </select>
                    <div class="form-text">Alamat ini akan tampil pada surat jalan.</div>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Detail Alamat Tujuan <span class="text-danger">*</span></label>
                    <textarea
                        name="alamat_tujuan"
                        id="alamat-tujuan"
                        class="form-control"
                        rows="3"
                        required
                        placeholder="Alamat tujuan pengiriman..."><?= esc((string) ($data_form['alamat_tujuan'] ?? '')) ?></textarea>

                    <input type="hidden" id="alamat-pelanggan-default" value="<?= esc((string) ($data_form['alamat_pelanggan'] ?? '')) ?>">
                    <input type="hidden" id="alamat-toko-default" value="<?= esc((string) ($data_form['alamat_toko'] ?? '')) ?>">
                </div>


                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kurir / Pengiriman</label>
                    <input type="text" name="kurir_pengiriman" class="form-control" value="<?= esc($data_form['kurir_pengiriman'] ?? '') ?>" placeholder="Contoh: Ambil di toko / Gojek / Grab / Maxim">
                    <div class="form-text">Tampil di surat jalan.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Resi / Driver</label>
                    <input type="text" name="no_resi_pengiriman" class="form-control" value="<?= esc($data_form['no_resi_pengiriman'] ?? '') ?>" placeholder="Opsional">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Biaya Pengiriman Dibayar UMKM</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" name="biaya_pengiriman" class="form-control text-end" value="<?= esc(number_format((float) ($data_form['biaya_pengiriman'] ?? 0), 2, '.', ',')) ?>" placeholder="0.00">
                    </div>
                    <input type="hidden" name="id_coa_biaya_pengiriman" value="<?= esc($data_form['id_coa_biaya_pengiriman'] ?? '') ?>">
                    <div class="form-text">Jika diisi, saat posting akan dibuat jurnal: Beban Pengiriman / Kas.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="3" placeholder="Catatan penyerahan..."><?= esc($data_form['catatan'] ?? '') ?></textarea>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="h5 mb-1">Detail Produk</h2>
                   
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" onclick="muatDetailPesanan()">
                    <i class="bi bi-arrow-repeat me-1"></i>Muat Detail Pesanan
                </button>
            </div>

            <div class="table-responsive border rounded">
                <table class="table align-middle mb-0" id="table-detail">
                    <thead class="table-light">
                        <tr>
                            <th width="60" class="text-center">No</th>
                            <th>Produk</th>
                            <th width="130" class="text-end">Stok Gudang</th>
                            <th width="130" class="text-end">Qty Pesanan</th>
                            <th width="120" class="text-end">Qty Kirim</th>
                            <th width="170" class="text-end">HPP Rata-rata</th>
                            <th width="180" class="text-end">HPP Total</th>
                            <th width="190">Catatan</th>
                            <th width="70" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="detail-body">
                        <?php foreach ($detail_rows as $i => $d): ?>
                            <tr class="detail-row">
                                <td class="text-center row-no"><?= $i + 1 ?></td>

                                <td>
                                    <select name="detail[<?= $i ?>][id_produk]" class="form-select detail-produk" required onchange="refreshProduk(this)">
                                        <option value="">- Pilih Produk -</option>
                                        <?php foreach ($produk_options as $p): ?>
                                            <option
                                                value="<?= (int) $p->id_produk ?>"
                                                data-stok="<?= esc(number_format((float) ($p->qty_saldo ?? 0), 3, '.', '')) ?>"
                                                data-qty-pesanan="<?= esc(number_format((float) ($p->qty_pesanan ?? 0), 2, '.', '')) ?>"
                                                data-hpp="<?= esc(number_format((float) ($p->hpp_rata_rata ?? 0), 2, '.', '')) ?>"
                                                <?= ((string) ($d['id_produk'] ?? '') === (string) $p->id_produk) ? 'selected' : '' ?>>
                                                <?= esc(($p->kode_produk ?? '-') . ' - ' . ($p->nama_produk ?? '-') . ' - Stok: ' . rtrim(rtrim(number_format((float) ($p->qty_saldo ?? 0), 3, ',', '.'), '0'), ',')) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <td>
                                    <input type="text" class="form-control text-end detail-stok-display" value="<?= esc(rtrim(rtrim(number_format((float) ($d['qty_saldo'] ?? 0), 3, '.', ''), '0'), '.')) ?>" readonly>
                                    <input type="hidden" class="detail-stok" value="<?= esc(number_format((float) ($d['qty_saldo'] ?? 0), 3, '.', '')) ?>">
                                </td>

                                <td>
                                    <input type="text" class="form-control text-end detail-qty-pesanan-display" value="<?= esc((string) (int) ($d['qty_pesanan'] ?? 0)) ?>" readonly>
                                    <input type="hidden" class="detail-qty-pesanan" value="<?= esc(number_format((float) ($d['qty_pesanan'] ?? 0), 2, '.', '')) ?>">
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="detail[<?= $i ?>][qty]"
                                        class="form-control text-end detail-qty"
                                        min="1"
                                        step="1"
                                        value="<?= esc((string) (int) ($d['qty'] ?? 1)) ?>"
                                        oninput="refreshSubtotal(this)"
                                        required>
                                </td>

                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" class="form-control text-end detail-hpp-display" value="<?= esc(number_format((float) ($d['hpp_satuan'] ?? 0), 2, '.', ',')) ?>" readonly>
                                    </div>
                                    <input type="hidden" name="detail[<?= $i ?>][hpp_satuan]" class="detail-hpp" value="<?= esc(number_format((float) ($d['hpp_satuan'] ?? 0), 2, '.', '')) ?>">
                                </td>

                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" class="form-control text-end detail-hpp-total-display" value="<?= esc(number_format((float) ($d['hpp_total'] ?? 0), 2, '.', ',')) ?>" readonly>
                                    </div>
                                    <input type="hidden" name="detail[<?= $i ?>][hpp_total]" class="detail-hpp-total" value="<?= esc(number_format((float) ($d['hpp_total'] ?? 0), 2, '.', '')) ?>">
                                </td>

                                <td>
                                    <input type="text" name="detail[<?= $i ?>][catatan]" class="form-control" value="<?= esc($d['catatan'] ?? '') ?>" placeholder="Opsional">
                                </td>

                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBarisDetail(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (count($detail_rows) === 0): ?>
                            <tr class="empty-row">
                                <td colspan="9" class="text-center text-muted py-4">
                                    Pilih Pesanan Penjualan dan Gudang, lalu klik <strong>Muat Detail Pesanan</strong>.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                    <tfoot class="table-light">
                        <tr>
                            <th colspan="4" class="text-end">Total</th>
                            <th class="text-end" id="total-qty-display">0</th>
                            <th></th>
                            <th class="text-end" id="total-hpp-display">Rp 0.00</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="alert alert-info mt-3 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Produk dengan stok 0 tidak akan muncul. Jika produk pesanan belum ada stok, lakukan hasil produksi/posting stok barang jadi terlebih dahulu.
            </div>

            <div class="d-flex justify-content-between align-items-center gap-2 mt-4">
                <a href="<?= esc(admin_page_url('penjualan/penyerahan')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Draft
                </button>
            </div>
        </form>
    </div>
</div>


<div class="modal fade" id="modalScanPesananOnline" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4">
      <div class="modal-header text-white" style="background:linear-gradient(135deg,#2563eb,#6d5dfc,#f97316)">
        <h5 class="modal-title">Scan / Input Kode Pesanan Online</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted">Scan barcode/QR dari HP pelanggan, atau ketik nomor pesanan online.</p>
        <input type="text" class="form-control form-control-lg" id="kode-pesanan-online-scan" placeholder="Contoh: PJO-202606-0001" autocomplete="off">
        <div class="form-text">Setelah terbaca, data pelanggan dan pesanan akan terisi otomatis.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" onclick="cariPesananOnlineBarcode()">Gunakan Pesanan</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalPembayaranCodOnline" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content rounded-4" method="post" action="<?= esc(admin_page_url('penjualan/penyerahan/proses-barcode-cod')) ?>" id="formPembayaranCodOnline">
      <div class="modal-header text-white" style="background:linear-gradient(135deg,#2563eb,#6d5dfc,#f97316)">
        <h5 class="modal-title">Pembayaran COD Pesanan Online</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="kode_pesanan_online" id="cod-kode-pesanan">
        <input type="hidden" name="id_gudang" id="cod-id-gudang">
        <div class="alert alert-info">Pesanan COD akan langsung diproses saat uang diterima. Sistem otomatis membuat penyerahan, mengurangi stok, mencatat pembayaran tunai, dan membuat jurnal.</div>
        <div class="mb-3">
          <label class="form-label small fw-semibold text-muted">No Pesanan</label>
          <input type="text" class="form-control" id="cod-no-pesanan-display" readonly>
        </div>
        <div class="mb-3 rounded-4 p-3" style="background:#eef2ff">
          <div class="d-flex justify-content-between"><span>Total Bayar</span><strong id="cod-total-display" class="text-primary">Rp 0.00</strong></div>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold text-muted">Uang yang diterima dari pelanggan</label>
          <input type="number" step="0.01" min="0" name="jumlah_diterima" id="cod-uang-diterima" class="form-control form-control-lg text-end" required oninput="hitungKembalianCod()">
        </div>
        <div class="mb-0 rounded-4 p-3 border">
          <div class="d-flex justify-content-between"><span>Uang kembalian ke pelanggan</span><strong id="cod-kembalian-display" style="color:#f97316">Rp 0.00</strong></div>
        </div>
        <input type="hidden" id="cod-total-value" value="0">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" onclick="return validasiCodOnline()"><i class="bi bi-cash-coin me-1"></i>Terima Uang & Serahkan Barang</button>
      </div>
    </form>
  </div>
</div>

<script>
let produkTersedia = <?= json_encode($produk_js, JSON_UNESCAPED_UNICODE) ?>;
const pesananPenyerahan = <?= json_encode($pesanan_js, JSON_UNESCAPED_UNICODE) ?>;
const autoLoadScanPenyerahan = <?= !empty($data_form['auto_load_scan']) ? 'true' : 'false' ?>;
const autoOpenCodOnline = <?= !empty($data_form['auto_cod_online']) ? 'true' : 'false' ?>;
const autoCodPesanan = {
    no_pesanan_penjualan: <?= json_encode((string)($data_form['kode_pesanan_online'] ?? ''), JSON_UNESCAPED_UNICODE) ?>,
    total: <?= json_encode((float)($data_form['total_pesanan'] ?? 0), JSON_UNESCAPED_UNICODE) ?>,
    metode_pembayaran_online: <?= json_encode((string)($data_form['metode_pembayaran_online'] ?? ''), JSON_UNESCAPED_UNICODE) ?>
};

let modalScanPesananOnline = null;
let modalPembayaranCodOnline = null;
let pendingAutoLoadDetailSetelahScan = false;
let pendingPembayaranCodSetelahPilihGudang = null;
function rupiahCod(n){ return 'Rp ' + (Number(n)||0).toLocaleString('id-ID',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function hitungKembalianCod(){ const total=Number(document.getElementById('cod-total-value')?.value||0); const terima=Number(document.getElementById('cod-uang-diterima')?.value||0); document.getElementById('cod-kembalian-display').textContent=rupiahCod(Math.max(0,terima-total)); }
function validasiCodOnline(){ const total=Number(document.getElementById('cod-total-value')?.value||0); const terima=Number(document.getElementById('cod-uang-diterima')?.value||0); if(terima<total){ alert('Uang yang diterima masih kurang dari total bayar.'); return false; } return confirm('Pastikan uang sudah diterima. Lanjut serahkan barang dan catat pembayaran COD?'); }
function bukaPembayaranCodOnline(ps){ const gudang=document.getElementById('id-gudang')?.value||''; if(!gudang){ pendingPembayaranCodSetelahPilihGudang = ps; alert('Pilih gudang dulu. Setelah gudang dipilih, popup pembayaran COD akan muncul otomatis.'); document.getElementById('id-gudang')?.focus(); return; } const total=Number(ps.total||0); document.getElementById('cod-kode-pesanan').value=ps.no_pesanan_penjualan||''; document.getElementById('cod-id-gudang').value=gudang; document.getElementById('cod-no-pesanan-display').value=ps.no_pesanan_penjualan||''; document.getElementById('cod-total-value').value=total.toFixed(2); document.getElementById('cod-total-display').textContent=rupiahCod(total); document.getElementById('cod-uang-diterima').value=total.toFixed(2); hitungKembalianCod(); const el=document.getElementById('modalPembayaranCodOnline'); if(window.bootstrap&&el){ modalPembayaranCodOnline=bootstrap.Modal.getOrCreateInstance(el); modalPembayaranCodOnline.show(); setTimeout(()=>document.getElementById('cod-uang-diterima')?.focus(),250); } else { alert('Total COD: '+rupiahCod(total)+'. Masukkan uang diterima, lalu proses.'); } }
function bukaScanPesananOnline(){
    const el = document.getElementById('modalScanPesananOnline');
    if (window.bootstrap && el) { modalScanPesananOnline = bootstrap.Modal.getOrCreateInstance(el); modalScanPesananOnline.show(); setTimeout(()=>document.getElementById('kode-pesanan-online-scan')?.focus(),300); }
    else { const kode = prompt('Masukkan kode pesanan online'); if(kode) cariPesananOnlineBarcode(kode); }
}
async function cariPesananOnlineBarcode(kodeManual){
    const input = document.getElementById('kode-pesanan-online-scan');
    const kode = (kodeManual || input?.value || '').trim();
    if(!kode){ alert('Kode pesanan wajib diisi.'); return; }
    try{
        const url = '<?= esc(admin_page_url('penjualan/penyerahan/load-pesanan-online-barcode')) ?>' + '&kode=' + encodeURIComponent(kode);
        const res = await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest'}});
        const data = await res.json();
        if(!data.success){ alert(data.message || 'Pesanan tidak ditemukan.'); return; }
        const ps = data.pesanan || {};
        let select = document.getElementById('id-pesanan-penjualan');
        let opt = Array.from(select.options).find(o => String(o.value) === String(ps.id_pesanan_penjualan));
        if(!opt){
            opt = new Option((ps.no_pesanan_penjualan||kode)+' - '+(ps.pelanggan_label||'-'), ps.id_pesanan_penjualan, true, true);
            opt.dataset.idPelanggan = ps.id_pelanggan || '';
            opt.dataset.pelanggan = ps.pelanggan_label || '';
            opt.dataset.alamatPelanggan = ps.alamat_pelanggan || '';
            select.add(opt);
        }
        select.value = ps.id_pesanan_penjualan;
        document.getElementById('id-pelanggan').value = ps.id_pelanggan || '';
        document.getElementById('pelanggan-display').value = ps.pelanggan_label || '';
        document.getElementById('alamat-pelanggan-default').value = ps.alamat_pelanggan || '';
        document.getElementById('alamat-tujuan').value = ps.alamat_pelanggan || document.getElementById('alamat-toko-default')?.value || '-';
        modalScanPesananOnline?.hide();
        if(String(ps.metode_pembayaran_online||'').toLowerCase()==='cod'){
            bukaPembayaranCodOnline(ps);
            return;
        }

        // Setelah scan barcode, detail pesanan langsung dimuat otomatis jika gudang sudah dipilih.
        // Jika gudang belum dipilih, sistem menunggu pilihan gudang lalu otomatis memuat detail tanpa klik tombol lagi.
        if(document.getElementById('id-gudang').value){
            await muatDetailPesanan(true);
        } else {
            pendingAutoLoadDetailSetelahScan = true;
            const body = document.getElementById('detail-body');
            if (body) {
                body.innerHTML = `
                    <tr class="empty-row">
                        <td colspan="9" class="text-center text-muted py-4">
                            Pesanan hasil scan sudah terisi. Pilih <strong>Gudang</strong>, lalu detail produk akan dimuat otomatis.
                        </td>
                    </tr>
                `;
            }
            document.getElementById('id-gudang')?.focus();
        }
    }catch(e){ alert('Gagal membaca pesanan online.'); }
}
document.addEventListener('keydown', function(e){ if(e.key==='Enter' && document.activeElement?.id==='kode-pesanan-online-scan'){ e.preventDefault(); cariPesananOnlineBarcode(); }});


function angka(value) {
    value = String(value ?? '0').replace(/,/g, '');
    const n = Number(value);
    return Number.isFinite(n) ? n : 0;
}

function formatQty(value) {
    const n = angka(value);
    if (Number.isInteger(n)) {
        return n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }

    return n.toLocaleString('id-ID', { maximumFractionDigits: 3 });
}

function formatRupiah(value) {
    return 'Rp ' + angka(value).toLocaleString('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function aturAlamatTujuan() {
    const jenis = document.getElementById('jenis-alamat-tujuan')?.value || 'pelanggan';
    const textarea = document.getElementById('alamat-tujuan');
    const alamatPelanggan = document.getElementById('alamat-pelanggan-default')?.value || '';
    const alamatToko = document.getElementById('alamat-toko-default')?.value || '';

    if (!textarea) {
        return;
    }

    if (jenis === 'pelanggan') {
        textarea.value = alamatPelanggan;
        textarea.readOnly = false;
        textarea.placeholder = 'Alamat pelanggan belum tersedia. Isi manual jika kosong.';
        return;
    }

    if (jenis === 'toko') {
        textarea.value = alamatToko;
        textarea.readOnly = false;
        textarea.placeholder = 'Alamat toko/entitas belum tersedia. Isi manual jika kosong.';
        return;
    }

    textarea.readOnly = false;

    if (textarea.value === alamatPelanggan || textarea.value === alamatToko) {
        textarea.value = '';
    }

    textarea.placeholder = 'Masukkan alamat tujuan lain...';
}

function refreshNomorBaris() {
    document.querySelectorAll('#detail-body .detail-row').forEach((row, index) => {
        row.querySelector('.row-no').textContent = index + 1;

        row.querySelectorAll('select, input').forEach((input) => {
            if (!input.name) return;
            input.name = input.name.replace(/detail\[\d+\]/, 'detail[' + index + ']');
        });
    });
}

function opsiProduk(selectedId = '') {
    let html = '<option value="">- Pilih Produk -</option>';

    produkTersedia.forEach((p) => {
        const selected = String(selectedId) === String(p.id_produk) ? 'selected' : '';
        html += `
            <option
                value="${p.id_produk}"
                data-stok="${p.qty_saldo}"
                data-qty-pesanan="${p.qty_pesanan}"
                data-hpp="${p.hpp_rata_rata}"
                ${selected}>
                ${p.label}
            </option>
        `;
    });

    return html;
}

function tambahBarisDetail(row = {}) {
    const tbody = document.getElementById('detail-body');
    tbody.querySelector('.empty-row')?.remove();

    const index = tbody.querySelectorAll('.detail-row').length;
    const idProduk = row.id_produk ?? '';
    const qtySaldo = angka(row.qty_saldo ?? 0);
    const qtyPesanan = angka(row.qty_pesanan ?? row.qty ?? 0);
    const qty = parseInt(row.qty ?? Math.min(qtyPesanan, qtySaldo), 10) || 1;
    const hpp = angka(row.hpp_satuan ?? row.hpp_rata_rata ?? 0);
    const hppTotal = qty * hpp;
    const catatan = String(row.catatan ?? '').replaceAll('"', '&quot;');

    const tr = document.createElement('tr');
    tr.className = 'detail-row';

    tr.innerHTML = `
        <td class="text-center row-no">${index + 1}</td>

        <td>
            <select name="detail[${index}][id_produk]" class="form-select detail-produk" required onchange="refreshProduk(this)">
                ${opsiProduk(idProduk)}
            </select>
        </td>

        <td>
            <input type="text" class="form-control text-end detail-stok-display" value="${formatQty(qtySaldo)}" readonly>
            <input type="hidden" class="detail-stok" value="${qtySaldo}">
        </td>

        <td>
            <input type="text" class="form-control text-end detail-qty-pesanan-display" value="${formatQty(qtyPesanan)}" readonly>
            <input type="hidden" class="detail-qty-pesanan" value="${qtyPesanan}">
        </td>

        <td>
            <input type="number" name="detail[${index}][qty]" class="form-control text-end detail-qty" min="1" step="1" value="${qty}" oninput="refreshSubtotal(this)" required>
        </td>

        <td>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text" class="form-control text-end detail-hpp-display" value="${hpp.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}" readonly>
            </div>
            <input type="hidden" name="detail[${index}][hpp_satuan]" class="detail-hpp" value="${hpp.toFixed(2)}">
        </td>

        <td>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text" class="form-control text-end detail-hpp-total-display" value="${hppTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}" readonly>
            </div>
            <input type="hidden" name="detail[${index}][hpp_total]" class="detail-hpp-total" value="${hppTotal.toFixed(2)}">
        </td>

        <td>
            <input type="text" name="detail[${index}][catatan]" class="form-control" value="${catatan}" placeholder="Opsional">
        </td>

        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBarisDetail(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    refreshSubtotal(tr.querySelector('.detail-qty'));
}

function hapusBarisDetail(button) {
    const tbody = document.getElementById('detail-body');
    const rows = tbody.querySelectorAll('.detail-row');

    if (rows.length <= 1) {
        alert('Minimal harus ada 1 baris produk.');
        return;
    }

    button.closest('tr').remove();
    refreshNomorBaris();
    refreshTotal();
}

function refreshProduk(select) {
    const tr = select.closest('tr');
    const option = select.options[select.selectedIndex];

    const stok = angka(option.dataset.stok ?? 0);
    const qtyPesanan = angka(option.dataset.qtyPesanan ?? 0);
    const hpp = angka(option.dataset.hpp ?? 0);

    tr.querySelector('.detail-stok-display').value = formatQty(stok);
    tr.querySelector('.detail-stok').value = stok;
    tr.querySelector('.detail-qty-pesanan-display').value = formatQty(qtyPesanan);
    tr.querySelector('.detail-qty-pesanan').value = qtyPesanan;
    tr.querySelector('.detail-hpp-display').value = hpp.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    tr.querySelector('.detail-hpp').value = hpp.toFixed(2);

    const qtyInput = tr.querySelector('.detail-qty');
    const qtyMaks = Math.max(1, Math.min(stok, qtyPesanan));
    if (angka(qtyInput.value) <= 0 || angka(qtyInput.value) > qtyMaks) {
        qtyInput.value = parseInt(qtyMaks, 10) || 1;
    }

    refreshSubtotal(qtyInput);
}

function refreshSubtotal(element) {
    const tr = element.closest('tr');

    const qtyInput = tr.querySelector('.detail-qty');
    const stok = angka(tr.querySelector('.detail-stok')?.value ?? 0);
    const qtyPesanan = angka(tr.querySelector('.detail-qty-pesanan')?.value ?? 0);
    const hpp = angka(tr.querySelector('.detail-hpp')?.value ?? 0);

    let qty = parseInt(qtyInput.value || '0', 10);
    if (!Number.isFinite(qty) || qty < 1) {
        qty = 1;
    }

    const maxQty = Math.floor(Math.min(stok, qtyPesanan));
    if (maxQty > 0 && qty > maxQty) {
        qty = maxQty;
    }

    qtyInput.value = qty;

    const hppTotal = qty * hpp;

    tr.querySelector('.detail-hpp-total-display').value = hppTotal.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    tr.querySelector('.detail-hpp-total').value = hppTotal.toFixed(2);

    refreshTotal();
}

function refreshTotal() {
    let totalQty = 0;
    let totalHpp = 0;

    document.querySelectorAll('#detail-body .detail-row').forEach((tr) => {
        totalQty += parseInt(tr.querySelector('.detail-qty')?.value || '0', 10) || 0;
        totalHpp += angka(tr.querySelector('.detail-hpp-total')?.value || 0);
    });

    document.getElementById('total-qty-display').textContent = totalQty.toLocaleString('id-ID');
    document.getElementById('total-hpp-display').textContent = formatRupiah(totalHpp);
}

async function muatDetailPesanan(dariScan = false) {
    const idPesanan = document.getElementById('id-pesanan-penjualan').value;
    const idGudang = document.getElementById('id-gudang').value;

    if (!idPesanan) {
        if (!dariScan) alert('Pilih Pesanan Penjualan terlebih dahulu.');
        return;
    }

    if (!idGudang) {
        if (!dariScan) alert('Pilih Gudang terlebih dahulu.');
        return;
    }

    const tbodyLoading = document.getElementById('detail-body');
    if (tbodyLoading) {
        tbodyLoading.innerHTML = `
            <tr class="empty-row">
                <td colspan="9" class="text-center text-muted py-4">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Memuat detail pesanan otomatis...
                </td>
            </tr>
        `;
    }

    try {
        const url = '<?= esc(admin_url('index.php?menu=penjualan/penyerahan/load-detail-pesanan')) ?>'
            + '&id_pesanan_penjualan=' + encodeURIComponent(idPesanan)
            + '&id_gudang=' + encodeURIComponent(idGudang)
            + '<?= !empty($data_form['id_penyerahan_penjualan']) ? '&id_penyerahan_penjualan=' . (int) $data_form['id_penyerahan_penjualan'] : '' ?>';

        const response = await fetch(url);
        const data = await response.json();

        if (!data.success) {
            alert(data.message || 'Gagal memuat detail pesanan.');
            return;
        }

        document.getElementById('id-pelanggan').value = data.pesanan.id_pelanggan || '';
        document.getElementById('pelanggan-display').value = data.pesanan.pelanggan_label || '';
        document.getElementById('alamat-pelanggan-default').value = data.pesanan.alamat_pelanggan || '';
        aturAlamatTujuan();

        produkTersedia = data.produk_options || [];

        const tbody = document.getElementById('detail-body');
        tbody.innerHTML = '';

        data.rows.forEach((row) => {
            tambahBarisDetail(row);
        });

        if (data.warning) {
            alert(data.warning);
        }

        pendingAutoLoadDetailSetelahScan = false;
        refreshNomorBaris();
        refreshTotal();
    } catch (error) {
        alert('Gagal memuat detail pesanan: ' + error.message);
    }
}

document.getElementById('id-pesanan-penjualan')?.addEventListener('change', function () {
    const option = this.options[this.selectedIndex];

    document.getElementById('id-pelanggan').value = option.dataset.idPelanggan || '';
    document.getElementById('pelanggan-display').value = option.dataset.pelanggan || '';
    document.getElementById('alamat-pelanggan-default').value = option.dataset.alamatPelanggan || '';

    aturAlamatTujuan();

    document.getElementById('detail-body').innerHTML = `
        <tr class="empty-row">
            <td colspan="9" class="text-center text-muted py-4">
                Klik <strong>Muat Detail Pesanan</strong> setelah memilih gudang.
            </td>
        </tr>
    `;

    refreshTotal();

    // Jika pesanan dipilih/terisi dari barcode dan gudang sudah ada, detail langsung dimuat otomatis.
    if (document.getElementById('id-pesanan-penjualan')?.value && document.getElementById('id-gudang')?.value) {
        setTimeout(() => muatDetailPesanan(true), 250);
    }
});

document.getElementById('id-gudang')?.addEventListener('change', function () {
    const idPesanan = document.getElementById('id-pesanan-penjualan')?.value || '';
    if (pendingPembayaranCodSetelahPilihGudang && this.value) {
        const ps = pendingPembayaranCodSetelahPilihGudang;
        pendingPembayaranCodSetelahPilihGudang = null;
        setTimeout(() => bukaPembayaranCodOnline(ps), 250);
        return;
    }
    if (idPesanan) {
        document.getElementById('detail-body').innerHTML = `
            <tr class="empty-row">
                <td colspan="9" class="text-center text-muted py-4">
                    Gudang berubah. Detail pesanan akan dimuat otomatis...
                </td>
            </tr>
        `;
        refreshTotal();
        setTimeout(() => muatDetailPesanan(true), 250);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    aturAlamatTujuan();
    refreshNomorBaris();

    document.querySelectorAll('#detail-body .detail-row').forEach((tr) => {
        refreshSubtotal(tr.querySelector('.detail-qty'));
    });

    refreshTotal();

    // Jika form dibuka dari hasil scan barcode di halaman riwayat/index,
    // pesanan dan gudang sudah terisi dari URL, maka detail produk langsung dimuat otomatis.
    if (autoOpenCodOnline && autoCodPesanan.no_pesanan_penjualan) {
        setTimeout(() => bukaPembayaranCodOnline(autoCodPesanan), 450);
        return;
    }
    if (autoLoadScanPenyerahan && document.getElementById('id-pesanan-penjualan')?.value && document.getElementById('id-gudang')?.value) {
        setTimeout(() => muatDetailPesanan(true), 350);
    }
});
</script>