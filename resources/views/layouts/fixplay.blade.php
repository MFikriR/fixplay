<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Fixplay')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Tema Fixplay -->
  <link rel="stylesheet" href="{{ asset('css/fixplay.css') }}">
  @stack('styles')

  <style>
    .navbar{ display:none !important; }
    .container{ max-width:100% !important; padding:0; }
    .fix-shell{ display:flex; min-height:100vh; background:#d1d5db; }
    .shell-aside{
      width:270px;
      background: linear-gradient(180deg,#4c1d95 0%,#7c3aed 40%,#3b82f6 100%);
      color:#ecf0ff; padding:22px 18px;
      box-shadow:8px 0 30px rgba(0,0,0,.15) inset;
      overflow:hidden; transition: width .25s ease, padding .25s ease, border-width .25s ease;
    }
    .fix-shell.sidebar-collapsed .shell-aside{ width:0; padding:0; border-width:0; }
    .shell-main{ flex:1; display:flex; flex-direction:column; }
    .topbar{
      position: sticky; top:0; z-index:1030; background:#fff; color:#111827;
      padding:12px 20px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center;
    }
    .topbar .title{ font-weight:900; font-size:20px; }
    .content-pad{ padding:24px; }

    .menu a{ display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:12px;
             color:#ecf0ff; text-decoration:none; font-weight:700; margin-bottom:6px; }
    .menu a:hover{ background: rgba(255,255,255,.15); }
    .menu a.active{ background: rgba(255,255,255,.22); box-shadow:0 0 0 1px rgba(255,255,255,.25) inset; }
    .menu i{ font-size:22px; }

    .card-dark{
      background:
        radial-gradient(120% 120% at 0% 0%, rgba(124,58,237,.25), transparent 40%),
        radial-gradient(120% 120% at 100% 0%, rgba(59,130,246,.22), transparent 45%),
        linear-gradient(180deg,#151528,#0f1020);
      border:1px solid rgba(122,92,255,.25); color:#eef2ff; border-radius:14px;
      box-shadow:0 10px 30px rgba(0,0,0,.35), 0 0 18px rgba(124,58,237,.12) inset;
    }
    .card-dark .card-header{
      background:rgba(15,16,32,.55); border-bottom:1px solid rgba(122,92,255,.25);
      color:#eaeaff; border-radius:14px 14px 0 0; font-weight:800;
    }
    .table thead th{ color:#cfd3ff; background:rgba(25,25,45,.6); }
    .table td,.table th{ border-color:rgba(122,92,255,.15) !important; }

    .notif-menu{
      min-width:320px;
      background:
        radial-gradient(100% 140% at 0% 0%, rgba(124,58,237,.25), transparent 40%),
        radial-gradient(120% 120% at 100% 0%, rgba(59,130,246,.22), transparent 45%),
        linear-gradient(180deg,#151528,#0f1020);
      color:#eef2ff; border:1px solid rgba(122,92,255,.35);
    }
    .notif-menu .list-group-item{ background:transparent; color:#eef2ff; border-color:rgba(122,92,255,.18); }
    .notif-menu .list-group-item .small{ color:#cdd1ff; }

    @media (max-width: 992px){
      .fix-shell{ flex-direction:column; }
      .shell-aside{ width:100%; border-bottom:1px solid #dbeafe; border-radius:0 0 16px 16px; }
      .fix-shell.sidebar-collapsed .shell-aside{ width:0; padding:0; border:0; }
    }
    @media print{ .shell-aside,.topbar{ display:none !important; } .content-pad{ padding:0; } }
  </style>
</head>
<body>
<div class="fix-shell">
  <aside class="shell-aside d-print-none">
    @include('partials.sidebar')
  </aside>

  <main class="shell-main">
    <div class="topbar d-print-none">
      <button id="sidebarToggle" class="btn btn-outline-dark me-2" type="button" title="Tutup/Buka menu">
        <i class="bi bi-list"></i>
      </button>
      <div class="title">@yield('page_title')</div>

      <div class="ms-auto">
        <div class="dropdown">
          <button id="notifBtn" class="btn btn-outline-dark position-relative" type="button"
                  data-bs-toggle="dropdown" aria-expanded="false" title="Notifikasi">
            <i class="bi bi-bell"></i>
            <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
          </button>
          <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg notif-menu">
            <div class="p-2 border-bottom d-flex justify-content-between align-items-center">
              <strong>Notifikasi</strong>
              <button class="btn btn-sm btn-link text-decoration-none" id="notifClear">Tandai sudah dibaca</button>
            </div>
            <div id="notifList" class="list-group list-group-flush" style="max-height:320px;overflow:auto;">
              <div class="p-3 text-muted">Belum ada notifikasi.</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="content-pad">
      @yield('page_content')
    </div>
  </main>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

{{-- Persist toggle sidebar + notifikasi global + dialog konfirmasi --}}
<script>
(function(){
  // Sidebar persist
  const KEY_SIDEBAR='fixplay.sidebar.collapsed';
  const root=document.querySelector('.fix-shell');
  const btn=document.getElementById('sidebarToggle');
  function apply(v){ if(!root) return; v?root.classList.add('sidebar-collapsed'):root.classList.remove('sidebar-collapsed'); }
  apply(localStorage.getItem(KEY_SIDEBAR)==='1');
  btn?.addEventListener('click',()=>{ const next=!root.classList.contains('sidebar-collapsed'); apply(next); localStorage.setItem(KEY_SIDEBAR,next?'1':'0'); });

  // Notifikasi
  const KEY_TIMERS='fixplay.rental.timers', KEY_INBOX='fixplay.rental.inbox', POLL_MS=15000;
  const notifBadge=document.getElementById('notifBadge'), notifList=document.getElementById('notifList'), clearBtn=document.getElementById('notifClear');
  const jget=(k,d)=>{try{const v=localStorage.getItem(k);return v===null?d:JSON.parse(v)}catch(e){return d}};
  const jset=(k,v)=>localStorage.setItem(k,JSON.stringify(v));
  function renderInbox(){
    const inbox=jget(KEY_INBOX,[]);
    if(notifBadge){ if(inbox.length){notifBadge.textContent=String(inbox.length);notifBadge.classList.remove('d-none');}else notifBadge.classList.add('d-none'); }
    if(!notifList) return;
    notifList.innerHTML='';
    if(!inbox.length){ notifList.innerHTML='<div class="p-3 text-muted">Belum ada notifikasi.</div>'; return; }
    inbox.slice().reverse().forEach(it=>{
      const a=document.createElement('a'); a.href='#'; a.className='list-group-item list-group-item-action'; a.dataset.id=it.id;
      a.innerHTML=`<div class="d-flex w-100 justify-content-between"><div><strong>${it.title}</strong></div><small class="small">${new Date(it.time).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'})}</small></div>${it.detail?`<div class="small mt-1">${it.detail}</div>`:''}`;
      a.addEventListener('click',e=>{e.preventDefault();const cur=jget(KEY_INBOX,[]);const idx=cur.findIndex(x=>String(x.id)===String(it.id)); if(idx>=0){cur.splice(idx,1); jset(KEY_INBOX,cur); renderInbox();}});
      notifList.appendChild(a);
    });
  }
  function addNotification(title,detail){ const inbox=jget(KEY_INBOX,[]); inbox.push({id:Date.now()+'-'+Math.random().toString(36).slice(2),title,detail,time:new Date().toISOString()}); jset(KEY_INBOX,inbox); renderInbox(); }
  function pollTimers(){
    const now=new Date(); const timers=jget(KEY_TIMERS,[]); let changed=false;
    timers.forEach(t=>{ const endAt=new Date(t.endAt); if(!t.notified && endAt<=now){ addNotification('Waktu rental habis',`Unit ${t.unit} telah melewati waktu selesai.`); t.notified=true; changed=true; }});
    const keep=timers.filter(t=>(Date.now()-new Date(t.endAt))<24*3600*1000); if(changed||keep.length!==timers.length) jset(KEY_TIMERS,keep);
  }
  clearBtn?.addEventListener('click',e=>{e.preventDefault(); jset(KEY_INBOX,[]); renderInbox();});
  window.addEventListener('storage',e=>{ if(e.key===KEY_INBOX||e.key===KEY_TIMERS) renderInbox(); });
  document.addEventListener('visibilitychange',()=>{ if(!document.hidden){ renderInbox(); pollTimers(); }});
  renderInbox(); pollTimers(); setInterval(pollTimers,POLL_MS);
})();
</script>

<!-- Modal konfirmasi global -->
<div class="modal fade" id="fxConfirm" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content fx-neon-card">
      <div class="modal-body">
        <div class="d-flex align-items-start gap-3">
          <div class="fx-neon-icon"><i class="bi bi-exclamation-triangle"></i></div>
          <div>
            <h5 class="m-0 fw-bold">Konfirmasi</h5>
            <div id="fxConfirmText" class="mt-1 text-neon-sub">Yakin?</div>
          </div>
        </div>
      </div>
      <div class="modal-footer fx-neon-footer">
        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
        <button id="fxConfirmOk" type="button" class="btn fx-btn-primary">OK</button>
      </div>
    </div>
  </div>
</div>
<style>
  .fx-neon-card{
    background:
      radial-gradient(120% 140% at 0% 0%, rgba(124,58,237,.18), transparent 45%),
      radial-gradient(120% 140% at 100% 0%, rgba(59,130,246,.15), transparent 50%),
      linear-gradient(180deg,#151528,#0f1020);
    color:#eef2ff;border:1px solid rgba(139,92,246,.55);
    box-shadow:0 0 0 2px rgba(139,92,246,.25) inset,0 10px 30px rgba(0,0,0,.55),0 0 22px rgba(139,92,246,.35);
    border-radius:16px;
  }
  .fx-neon-icon{ width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;
    background:rgba(139,92,246,.15);border:1px solid rgba(139,92,246,.6);box-shadow:0 0 14px rgba(139,92,246,.45);
    font-size:20px;color:#c4b5fd;}
  .text-neon-sub{ color:#cdd1ff; }
  .fx-neon-footer{ border-top-color:rgba(139,92,246,.2); }
  .fx-btn-primary{ background:linear-gradient(135deg,#7c3aed,#3b82f6); border:0; color:#fff; font-weight:700; box-shadow:0 6px 18px rgba(124,58,237,.35); }
  .fx-btn-primary:hover{ filter:brightness(1.06); }
</style>
<script>
(function(){
  let pendingForm=null;
  const modalEl=document.getElementById('fxConfirm'); if(!modalEl) return;
  const modal=new bootstrap.Modal(modalEl);
  const txt=document.getElementById('fxConfirmText');
  const okBtn=document.getElementById('fxConfirmOk');

  document.querySelectorAll('form.confirm-delete, form[data-confirm]').forEach(form=>{
    form.addEventListener('submit',function(e){
      e.preventDefault(); pendingForm=this; txt.textContent=this.dataset.confirm||'Yakin hapus?'; modal.show();
    });
  });
  okBtn.addEventListener('click',function(){ if(pendingForm){ pendingForm.submit(); pendingForm=null; } modal.hide(); });
})();
</script>

@stack('scripts')
</body>
</html>
