<?php
declare(strict_types=1);

require_once __DIR__ . '/_helper.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = po_id_entitas();
$no_hp = trim((string) ($_GET['no_hp'] ?? ''));
$rows = collect();

if ($no_hp !== '') {
    $rows = Capsule::table('tb_pesanan_penjualan as pp')
        ->join('tb_pelanggan as p', 'p.id_pelanggan', '=', 'pp.id_pelanggan')
        ->where('pp.id_entitas', $id_entitas)
        ->where('p.no_hp', $no_hp)
        ->select(['pp.*', 'p.nama_pelanggan', 'p.no_hp', 'p.alamat'])
        ->orderBy('pp.tanggal_pesanan', 'desc')
        ->orderBy('pp.id_pesanan_penjualan', 'desc')
        ->limit(30)
        ->get();
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Cek Pesanan Online</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
*{box-sizing:border-box}:root{--blue:#2563eb;--purple:#7c3aed;--orange:#f97316;--dark:#111827;--muted:#64748b;--line:#e5e7eb;--bg:#f7f8ff;--green:#16a34a;--red:#dc2626}
body{margin:0;font-family:Arial,Helvetica,sans-serif;background:radial-gradient(circle at top left,rgba(37,99,235,.12),transparent 34%),radial-gradient(circle at bottom right,rgba(249,115,22,.15),transparent 32%),var(--bg);color:var(--dark);font-size:14px}
.wrap{max-width:1120px;margin:0 auto;padding:28px 16px}
.hero{color:#fff;border-radius:28px;padding:30px;background:radial-gradient(circle at 82% 20%,rgba(255,255,255,.22),transparent 22%),linear-gradient(135deg,var(--blue),var(--purple),var(--orange));box-shadow:0 22px 54px rgba(37,99,235,.18);margin-bottom:18px;position:relative;overflow:hidden}
.hero:after{content:"";position:absolute;right:-80px;bottom:-120px;width:260px;height:260px;border-radius:999px;background:rgba(255,255,255,.16);animation:float 5s ease-in-out infinite}
.hero h1{margin:0;font-size:34px;font-weight:900;letter-spacing:-.03em}
.hero p{margin:8px 0 0;max-width:640px;color:rgba(255,255,255,.84);line-height:1.6}
.card{background:rgba(255,255,255,.92);border:1px solid rgba(37,99,235,.12);border-radius:22px;box-shadow:0 14px 34px rgba(15,23,42,.07);padding:18px;margin-bottom:16px;backdrop-filter:blur(8px)}
.form-row{display:grid;grid-template-columns:1fr auto;gap:10px}
input{width:100%;border:1px solid var(--line);border-radius:16px;padding:14px 15px;font:inherit;outline:none;background:#fff}
input:focus{border-color:rgba(124,58,237,.55);box-shadow:0 0 0 4px rgba(124,58,237,.10)}
.btn{border:0;color:#fff;border-radius:15px;padding:12px 15px;font-weight:900;cursor:pointer;background:linear-gradient(135deg,var(--blue),var(--purple));text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:6px;white-space:nowrap}
.btn:hover{transform:translateY(-1px);box-shadow:0 12px 24px rgba(37,99,235,.18)}
.btn-light{background:#fff;color:var(--blue);border:1px solid rgba(37,99,235,.18);box-shadow:none}
.actions-top{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
.table-wrap{overflow:auto;border:1px solid var(--line);border-radius:18px;background:#fff}
table{width:100%;border-collapse:collapse;min-width:820px}
th,td{padding:12px;border-bottom:1px solid var(--line);text-align:left;vertical-align:middle}
th{background:#f8fafc;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.04em}
tr:hover td{background:#fbfdff}
.order-no{font-weight:900;color:var(--blue);word-break:break-word}
.muted{color:var(--muted);font-size:12px}
.status{border-radius:999px;padding:7px 10px;font-size:12px;font-weight:900;display:inline-flex;white-space:nowrap}
.status-draft{background:#eef2ff;color:#2563eb}
.status-confirmed{background:#f5f3ff;color:#7c3aed}
.status-process{background:#fff7ed;color:#f97316}
.status-done{background:#ecfdf5;color:#16a34a}
.status-cancel{background:#fef2f2;color:#dc2626}
.pay-badge{font-weight:900;color:#111827}
.empty{text-align:center;color:var(--muted);padding:34px 12px}
.action-cell{display:flex;gap:10px;justify-content:flex-end;align-items:center;flex-wrap:nowrap;min-width:160px}
.action-btn{width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;padding:0;border-radius:14px;font-size:18px;text-decoration:none;border:1px solid rgba(148,163,184,.24);background:#fff;color:#0f172a;flex:0 0 44px;cursor:pointer}
.action-btn:hover{transform:translateY(-1px);box-shadow:0 10px 20px rgba(15,23,42,.08)}
.action-btn.upload{background:#eff6ff;color:#1d4ed8;border-color:rgba(37,99,235,.18)}
.action-btn.chat{background:linear-gradient(135deg,#fb923c,#8b5cf6);color:#fff;border:none}
.action-btn.jpg{background:linear-gradient(135deg,#2563eb,#4f46e5);color:#fff;border:none}
.action-icon{display:inline-flex;align-items:center;justify-content:center;font-size:18px;line-height:1}
.mobile-hint{display:none;color:var(--muted);font-size:12px;margin-top:8px}
.chat-float{position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:1000;display:none;align-items:center;justify-content:center;padding:16px}
.chat-float.show{display:flex}
.chat-window{width:min(720px,100%);height:min(720px,92vh);background:#fff;border-radius:26px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 28px 80px rgba(15,23,42,.28)}
.chat-head{padding:16px 18px;background:linear-gradient(135deg,var(--blue),var(--purple),var(--orange));color:#fff;display:flex;justify-content:space-between;gap:12px;align-items:center}
.chat-head h3{margin:0;font-size:20px}
.chat-close{border:0;background:rgba(255,255,255,.18);color:#fff;border-radius:999px;width:38px;height:38px;font-size:18px;cursor:pointer}
.chat-products{display:flex;gap:8px;overflow:auto;padding:12px;background:#f8fafc;border-bottom:1px solid var(--line)}
.prod-chip{flex:0 0 auto;border:1px solid rgba(37,99,235,.14);background:#fff;border-radius:16px;padding:8px 10px;font-size:12px;font-weight:800;cursor:pointer}
.quick{display:flex;gap:8px;overflow:auto;padding:10px 12px;border-bottom:1px solid var(--line)}
.quick button{flex:0 0 auto;border:1px solid rgba(124,58,237,.18);background:#fff;color:#1e3a8a;border-radius:999px;padding:9px 12px;font-weight:800;cursor:pointer}
.chat-body{flex:1;overflow:auto;padding:14px;background:linear-gradient(180deg,#fff,#f8fafc)}
.bubble{max-width:78%;padding:11px 13px;margin:9px 0;border-radius:20px;line-height:1.45;box-shadow:0 8px 18px rgba(15,23,42,.06)}
.bubble.customer{margin-left:auto;background:linear-gradient(135deg,var(--blue),var(--purple));color:#fff;border-bottom-right-radius:6px}
.bubble.admin{margin-right:auto;background:#fff;border:1px solid var(--line);border-bottom-left-radius:6px}
.time{font-size:11px;opacity:.65;margin-top:4px}
.chat-form{display:grid;grid-template-columns:1fr auto;gap:8px;padding:12px;border-top:1px solid var(--line);background:#fff}
.chat-form textarea{resize:none;border:1px solid #cbd5e1;border-radius:22px;padding:13px 15px;font:inherit;outline:0}
.chat-form textarea:focus{border-color:var(--purple);box-shadow:0 0 0 4px rgba(124,58,237,.10)}
.chat-form button{border:0;border-radius:999px;background:linear-gradient(135deg,var(--orange),var(--purple));color:#fff;font-weight:900;padding:0 18px}
@keyframes float{50%{transform:translateY(-16px)}}
@media(max-width:760px){
.form-row{grid-template-columns:1fr}
.btn{width:100%}
.hero h1{font-size:28px}
.bubble{max-width:88%}
.chat-form{grid-template-columns:1fr}
.chat-form button{padding:12px}
.actions-top{flex-direction:column}
table{min-width:760px}
.action-cell{gap:8px;min-width:150px}
.action-btn{width:40px;height:40px;flex-basis:40px;font-size:16px}
.action-icon{font-size:16px}
.mobile-hint{display:block}
.hero p{font-size:13px}
}
</style>
  <link rel="icon" type="image/png" href="<?= po_esc(po_base_url('assets/logo/icon-logo-apk.png')) ?>">
</head>
<body>
<div class="wrap">
    <section class="hero">
        <h1>Cek Pesanan</h1>
        <p>Masukkan nomor WhatsApp saat checkout untuk melihat daftar pesanan.</p>
    </section>

    <div class="card">
        <form method="get" action="<?= po_esc(po_url('cek.php')) ?>" class="form-row">
            <input type="hidden" name="entitas" value="<?= (int) $id_entitas ?>">
            <input type="text" name="no_hp" value="<?= po_esc($no_hp) ?>" placeholder="Contoh: 08123456789" required>
            <button type="submit" class="btn">🔎 Cek Pesanan</button>
        </form>

        <div class="actions-top">
            <a href="<?= po_esc(po_base_url('index.php')) ?>" class="btn btn-light">← Halaman Awal</a>
            <a href="<?= po_esc(po_url('index.php?entitas=' . (int) $id_entitas)) ?>" class="btn btn-light">🛒 Katalog Produk</a>
        </div>
    </div>

    <?php if ($no_hp !== ''): ?>
        <div class="card">
            <h2 style="margin:0 0 14px;">Daftar Pesanan</h2>

            <?php if ($rows->count() === 0): ?>
                <div class="empty">Tidak ada pesanan dengan No HP tersebut.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>No Pesanan</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Pembayaran</th>
                                <th style="text-align:right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>
                                    <div class="order-no"><?= po_esc($row->no_pesanan_penjualan) ?></div>
                                    <div class="muted"><?= po_esc(po_payment_method_label($row->metode_pembayaran_online ?? 'cod')) ?></div>
                                </td>
                                <td><?= po_esc(date('d/m/Y', strtotime((string) $row->tanggal_pesanan))) ?></td>
                                <td>
                                    <strong><?= po_esc($row->nama_pelanggan ?? '-') ?></strong>
                                    <div class="muted"><?= po_esc($row->no_hp ?? '-') ?></div>
                                </td>
                                <td><strong><?= po_uang($row->total ?? 0) ?></strong></td>
                                <td>
                                    <span class="status <?= po_esc(po_status_class($row->status_pesanan)) ?>">
                                        <?= po_esc(po_status_label($row->status_pesanan)) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="pay-badge">
                                        <?= po_esc(po_payment_status_label($row->status_pembayaran_online ?? 'belum_bayar')) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-cell">
                                        <a class="action-btn upload"
                                           title="Upload bukti bayar"
                                           aria-label="Upload bukti bayar"
                                           href="<?= po_esc(po_url('sukses.php?id=' . (int) $row->id_pesanan_penjualan . '&entitas=' . (int) $id_entitas)) ?>">
                                            <span class="action-icon">📤</span>
                                        </a>

                                        <button class="action-btn chat"
                                                type="button"
                                                title="Chat admin"
                                                aria-label="Chat admin"
                                                onclick="openChat(<?= (int) $row->id_pesanan_penjualan ?>,'<?= po_esc($row->no_hp ?? '') ?>')">
                                            <span class="action-icon">💬</span>
                                        </button>

                                        <a class="action-btn jpg"
                                           title="Simpan kode JPG"
                                           aria-label="Simpan kode JPG"
                                           href="<?= po_esc(po_url('simpan_jpg.php?id=' . (int) $row->id_pesanan_penjualan . '&entitas=' . (int) $id_entitas)) ?>">
                                            <span class="action-icon">🖼️</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mobile-hint">Geser tabel ke samping jika memakai HP.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div class="chat-float" id="chatModal">
    <div class="chat-window">
        <div class="chat-head">
            <div>
                <h3>Chat Admin</h3>
                <div id="chatOrderNo" style="font-size:12px;opacity:.82"></div>
            </div>
            <button class="chat-close" type="button" onclick="closeChat()">×</button>
        </div>

        <div class="chat-products" id="chatProducts"></div>

        <div class="quick">
            <button type="button" onclick="fillChat('Pesanan saya sudah sampai tahap mana?')">Status pesanan</button>
            <button type="button" onclick="fillChat('Bukti pembayaran saya sudah diterima?')">Bukti pembayaran</button>
            <button type="button" onclick="fillChat('Kapan pesanan saya diproses?')">Kapan diproses?</button>
            <button type="button" onclick="fillChat('Saya mau tanya stok produk di pesanan ini.')">Stok produk</button>
        </div>

        <div class="chat-body" id="chatRows">
            <div class="empty">Memuat chat...</div>
        </div>

        <form class="chat-form" id="chatForm">
            <textarea id="chatText" rows="1" placeholder="Tulis pesan..." required></textarea>
            <button type="submit">Kirim</button>
        </form>
    </div>
</div>

<script>
const ENTITAS_ID = <?= (int) $id_entitas ?>;
let activeChatId = 0;
let activeChatPhone = '';

function esc(t){
    return String(t || '').replace(/[&<>"']/g, m => ({
        '&':'&amp;',
        '<':'&lt;',
        '>':'&gt;',
        '"':'&quot;',
        "'":'&#039;'
    }[m]));
}

function openChat(id, phone){
    activeChatId = id;
    activeChatPhone = phone || '';
    document.getElementById('chatModal').classList.add('show');
    loadChat();
}

function closeChat(){
    document.getElementById('chatModal').classList.remove('show');
}

function fillChat(t){
    document.getElementById('chatText').value = t;
    document.getElementById('chatText').focus();
}

async function loadChat(){
    const res = await fetch(`<?= po_esc(po_url('chat_ajax.php')) ?>?id=${activeChatId}&no_hp=${encodeURIComponent(activeChatPhone)}&entitas=${ENTITAS_ID}`);
    const data = await res.json();

    if (!data.ok) {
        document.getElementById('chatRows').innerHTML = `<div class="empty">${esc(data.message)}</div>`;
        return;
    }

    document.getElementById('chatOrderNo').textContent = data.pesanan.no;

    document.getElementById('chatProducts').innerHTML = (data.produk || []).map(p =>
        `<button class="prod-chip" type="button" onclick="fillChat('Saya mau tanya produk ${esc(p.nama_produk)}.')">🍬 ${esc(p.nama_produk)} · ${esc(p.qty)} x ${esc(p.harga)}</button>`
    ).join('') || '<span class="muted">Produk belum terbaca</span>';

    document.getElementById('chatRows').innerHTML = (data.rows || []).map(r =>
        `<div class="bubble ${r.pengirim_tipe === 'customer' ? 'customer' : 'admin'}">
            <strong>${r.pengirim_tipe === 'customer' ? 'Anda' : esc(r.nama_pengirim || 'Admin')}</strong><br>
            ${esc(r.pesan).replace(/\n/g, '<br>')}
            <div class="time">${esc(r.tanggal)}</div>
        </div>`
    ).join('') || '<div class="empty">Belum ada chat. Silakan kirim pertanyaan.</div>';

    const body = document.getElementById('chatRows');
    body.scrollTop = body.scrollHeight;
}

document.getElementById('chatForm').addEventListener('submit', async function(e){
    e.preventDefault();

    const pesan = document.getElementById('chatText').value.trim();
    if (!pesan) return;

    const fd = new FormData();
    fd.append('id_pesanan_penjualan', activeChatId);
    fd.append('no_hp', activeChatPhone);
    fd.append('entitas', ENTITAS_ID);
    fd.append('pesan', pesan);

    const res = await fetch(`<?= po_esc(po_url('chat_ajax.php')) ?>`, {
        method: 'POST',
        body: fd
    });

    const data = await res.json();

    if (!data.ok) {
        alert(data.message);
        return;
    }

    document.getElementById('chatText').value = '';
    await loadChat();
});
</script>
</body>
</html>