<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$q = trim((string) ($_GET['q'] ?? ''));

if (!Capsule::schema()->hasTable('tb_pesanan_online_chat_general')) {
    echo '<div class="alert alert-warning">Tabel chat general belum tersedia. Jalankan SQL update terlebih dahulu.</div>';
    return;
}

$sessions = Capsule::table('tb_pesanan_online_chat_general')
    ->selectRaw('session_key, MAX(id_chat_general) as last_id, MAX(tanggal_dibuat) as terakhir, MAX(nama_pelanggan) as nama_pelanggan, MAX(no_hp) as no_hp, SUM(CASE WHEN pengirim_tipe = "customer" AND status_dibaca_admin = 0 THEN 1 ELSE 0 END) as unread')
    ->where('id_entitas', $id_entitas);
if ($q !== '') {
    $sessions->where(function ($w) use ($q) {
        $w->where('nama_pelanggan', 'like', "%{$q}%")
          ->orWhere('no_hp', 'like', "%{$q}%")
          ->orWhere('pesan', 'like', "%{$q}%");
    });
}
$sessions = $sessions->groupBy('session_key')->orderByDesc('terakhir')->limit(50)->get();
$active = trim((string) ($_GET['session'] ?? ($sessions[0]->session_key ?? '')));
$rows = collect();
if ($active !== '') {
    Capsule::table('tb_pesanan_online_chat_general')
        ->where('id_entitas', $id_entitas)->where('session_key', $active)
        ->where('pengirim_tipe', 'customer')
        ->update(['status_dibaca_admin' => 1]);
    $rows = Capsule::table('tb_pesanan_online_chat_general')
        ->where('id_entitas', $id_entitas)->where('session_key', $active)
        ->orderBy('id_chat_general')->get();
}
?>
<style>
.chat-admin-wrap{display:grid;grid-template-columns:330px 1fr;gap:16px}.chat-list,.chat-room{background:#fff;border:1px solid #e5e7eb;border-radius:18px;overflow:hidden;box-shadow:0 10px 26px rgba(15,23,42,.06)}.chat-search{padding:14px;border-bottom:1px solid #e5e7eb}.chat-search input{width:100%;border:1px solid #dbe2ea;border-radius:12px;padding:11px}.chat-session{display:block;padding:13px 14px;border-bottom:1px solid #eef2f7;color:#111827;text-decoration:none}.chat-session:hover,.chat-session.active{background:#eef2ff}.chat-session strong{display:block}.chat-session small{color:#64748b}.unread{float:right;background:#f97316;color:#fff;border-radius:999px;padding:3px 8px;font-size:11px}.chat-head{padding:16px 18px;color:#fff;background:linear-gradient(135deg,#2563eb,#6d5dfc,#f97316)}.chat-body{height:520px;overflow:auto;background:#f8fafc;padding:16px}.bubble{max-width:76%;padding:11px 13px;border-radius:18px;margin:8px 0}.bubble.customer{background:#fff;border:1px solid #e5e7eb}.bubble.admin{margin-left:auto;color:#fff;background:linear-gradient(135deg,#2563eb,#6d5dfc)}.time{font-size:11px;opacity:.65;margin-top:4px}.chat-form{display:grid;grid-template-columns:1fr auto;gap:10px;padding:14px;border-top:1px solid #e5e7eb}.chat-form textarea{border:1px solid #dbe2ea;border-radius:16px;padding:12px;resize:none}.btn-send{border:0;border-radius:16px;background:linear-gradient(135deg,#2563eb,#f97316);color:#fff;font-weight:800;padding:0 18px}@media(max-width:900px){.chat-admin-wrap{grid-template-columns:1fr}.chat-body{height:420px}}
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div><h3 class="mb-1">Log Chat Online</h3><div class="text-muted">Chat general pelanggan seperti WhatsApp Business.</div></div>
</div>
<div class="chat-admin-wrap">
  <aside class="chat-list">
    <form class="chat-search" method="get"><input type="hidden" name="menu" value="penjualan/chat-online"><input name="q" value="<?= esc($q) ?>" placeholder="Cari nama, no HP, atau pesan..."></form>
    <?php foreach($sessions as $s): ?><a class="chat-session <?= $active===$s->session_key?'active':'' ?>" href="<?= esc(admin_page_url('penjualan/chat-online') . '&session=' . urlencode($s->session_key)) ?>"><?php if((int)$s->unread>0): ?><span class="unread"><?= (int)$s->unread ?></span><?php endif; ?><strong><?= esc($s->nama_pelanggan ?: 'Pelanggan') ?></strong><small><?= esc($s->no_hp ?: '-') ?> · <?= esc(date('d/m/Y H:i', strtotime((string)$s->terakhir))) ?></small></a><?php endforeach; ?>
    <?php if($sessions->count()===0): ?><div class="p-3 text-muted">Belum ada chat.</div><?php endif; ?>
  </aside>
  <section class="chat-room">
    <div class="chat-head"><strong><?= $active!=='' ? 'Percakapan' : 'Pilih chat' ?></strong></div>
    <div class="chat-body" id="chatBody"><?php foreach($rows as $r): ?><div class="bubble <?= esc($r->pengirim_tipe) ?>"><strong><?= esc($r->nama_pengirim ?: ($r->pengirim_tipe==='admin'?'Admin':'Pelanggan')) ?></strong><br><?= nl2br(esc($r->pesan)) ?><div class="time"><?= esc(date('d/m/Y H:i', strtotime((string)$r->tanggal_dibuat))) ?></div></div><?php endforeach; ?><?php if($active==='' || $rows->count()===0): ?><div class="text-muted text-center p-4">Belum ada pesan pada percakapan ini.</div><?php endif; ?></div>
    <?php if($active!==''): ?><form class="chat-form" method="post" action="<?= esc(admin_page_url('penjualan/chat-online/kirim')) ?>"><input type="hidden" name="session" value="<?= esc($active) ?>"><textarea name="pesan" required rows="2" placeholder="Balas chat pelanggan..."></textarea><button class="btn-send" type="submit">Kirim</button></form><?php endif; ?>
  </section>
</div>
<script>
const b=document.getElementById('chatBody'); if(b){b.scrollTop=b.scrollHeight}
document.querySelectorAll('.chat-form').forEach(function(form){
  form.addEventListener('submit', async function(e){
    e.preventDefault();
    const btn=form.querySelector('button[type=submit]');
    const txt=form.querySelector('textarea[name=pesan]');
    const old=btn.textContent;
    btn.disabled=true; btn.textContent='Mengirim...';
    try{
      const res=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{'X-Requested-With':'XMLHttpRequest'}});
      const data=await res.json();
      if(!data.ok){ alert(data.message||'Gagal mengirim pesan.'); return; }
      const wrap=document.getElementById('chatBody');
      const div=document.createElement('div');
      div.className='bubble admin';
      div.innerHTML='<strong>'+escapeHtml(data.nama_pengirim||'Admin')+'</strong><br>'+escapeHtml(data.pesan).replace(/\n/g,'<br>')+'<div class="time">'+escapeHtml(data.tanggal||'')+'</div>';
      const empty=wrap.querySelector('.text-muted.text-center'); if(empty) empty.remove();
      wrap.appendChild(div); wrap.scrollTop=wrap.scrollHeight;
      txt.value=''; txt.focus();
    }catch(err){ alert('Gagal mengirim pesan. Cek koneksi/server.'); }
    finally{ btn.disabled=false; btn.textContent=old; }
  });
});
function escapeHtml(t){return String(t||'').replace(/[&<>"']/g,function(m){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m];});}
</script>
