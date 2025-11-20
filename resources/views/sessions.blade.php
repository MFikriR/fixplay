@extends('layouts.fixplay')


@section('page_title', 'Kasir Fixplay')

@section('page_content')
<h4 class="mb-3 text-dark fw-bold">Sesi PS (Durasi Tetap)</h4>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card card-dark h-100">
      <div class="card-body">
        <h6 class="mb-3 fw-bold">Buat Sesi & Tagih</h6>

        <form method="post" action="{{ route('sessions.fixed') }}" id="fixedForm">
          @csrf

          <label class="form-label">Pilih Unit PS</label>
          <select class="form-select" name="ps_unit_id" id="unitSel" required>
            <option value="">-- pilih --</option>
            @foreach($units as $u)
              <option value="{{ $u->id }}" data-rate="{{ $u->hourly_rate }}">
                {{ $u->name }} — Rp {{ number_format($u->hourly_rate,0,',','.') }}/jam
              </option>
            @endforeach
          </select>

          <div class="row mt-3 g-2">
            <div class="col-md-6">
              <label class="form-label">Tambahan Stik</label>
              <select class="form-select" id="extraSel" name="extra_controllers">
                @for($n=0;$n<=4;$n++)
                <option value="{{ $n }}">{{ $n }}</option>
                @endfor
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Arcade Controller</label>
              <select class="form-select" id="arcadeSel" name="arcade_controllers">
                @for($n=0;$n<=2;$n++)
                <option value="{{ $n }}">{{ $n }}</option>
                @endfor
              </select>
            </div>
          </div>

          <label class="form-label mt-3">Waktu Mulai</label>
          <input type="datetime-local" class="form-control" name="start_time" id="startInput"
                 value="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}" required>

          <label class="form-label mt-3">Durasi</label>
          <select class="form-select" id="hoursSel" name="hours">
            <option value="0.5">30 menit</option>
            @for($h=1;$h<=6;$h++)
            <option value="{{ $h }}">{{ $h }} jam</option>
            @endfor
          </select>

          <div class="row mt-3 g-2">
            <div class="col-md-4">
              <label class="form-label">Metode Bayar</label>
              <select name="payment_method" id="payMethod" class="form-select">
                <option value="Tunai">Tunai</option>
                <option value="QRIS">QRIS</option>
                <option value="Transfer">Transfer</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Dibayar</label>
              <input type="number" class="form-control" name="paid_amount" id="paidAmount" value="" min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Kembalian</label>
              <input type="text" class="form-control bg-white text-dark" id="changeLbl" value="Rp 0" readonly>
            </div>
          </div>

          <div class="mt-3 small">
            <div class="calc-line">Selesai jam: <span id="endLbl">—:—</span></div>
            <div class="calc-line">Total tagihan: <span id="billLbl">Rp 0</span></div>
          </div>

          <button class="btn btn-primary mt-3">Buat &amp; Tagih</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card card-dark h-100">
      <div class="card-header">Riwayat Sesi (Terakhir 20)</div>
      <div class="card-body p-0">
        <div class="table-responsive" style="max-height:520px;">
          <table class="table table-sm table-hover m-0 align-middle table-neon">
            <thead>
              <tr>
                <th>Unit</th>
                <th class="text-nowrap">Mulai</th>
                <th class="text-nowrap">Selesai</th>
                <th class="text-center">Durasi</th>
                <th class="text-end">Tagihan</th>
                <th class="text-end d-print-none">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($closed_sessions as $s)
              <tr>
                <td class="text-black">
                  {{ $s->ps_unit->name ?? '-' }}
                  @if(!empty($s->extra_controllers) && $s->extra_controllers > 0)
                    <span class="badge badge-addon badge-glow ms-2">+ Stik ×{{ $s->extra_controllers }}</span>
                  @endif
                  @if(!empty($s->arcade_controllers) && $s->arcade_controllers > 0)
                    <span class="badge badge-addon badge-glow ms-1">Arcade ×{{ $s->arcade_controllers }}</span>
                  @endif
                </td>
                <td class="text-nowrap">{{ \Carbon\Carbon::parse($s->start_time)->format('d-m-Y H:i') }}</td>
                <td class="text-nowrap">{{ $s->end_time ? \Carbon\Carbon::parse($s->end_time)->format('d-m-Y H:i') : '-' }}</td>
                <td class="text-center">
                  <span class="badge bg-secondary-subtle text-dark fw-semibold">
                    {{ intdiv($s->minutes ?? 0, 60) }} jam
                  </span>
                </td>
                <td class="text-end amount-mono">Rp {{ number_format($s->bill ?? 0,0,',','.') }}</td>
                <td class="text-end d-print-none">
                  <form class="d-inline confirm-delete" method="post"
                        action="{{ route('sessions.delete', ['sid' => $s->id]) }}"
                        onsubmit="return confirm('Hapus riwayat sesi ini? Penjualan terkait (jika ada) juga akan dihapus.');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                  </form>
                </td>
              </tr>
              @empty
              <tr><td colspan="6" class="text-center text-muted p-3">Belum ada sesi.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const EX_RATE = 10000;   // Rp 10.000/stiks/jam
  const ARC_RATE = 15000;  // Rp 15.000/arcade/jam
  const KEY_TIMERS = 'fixplay.rental.timers';

  function fmtIDR(n){ return (n||0).toLocaleString('id-ID'); }

  function getNumbers(){
    const unit = document.getElementById('unitSel');
    return {
      base:   parseFloat(unit?.selectedOptions[0]?.dataset?.rate || '0'),
      extra:  parseInt(document.getElementById('extraSel').value  || '0', 10),
      arcade: parseInt(document.getElementById('arcadeSel').value || '0', 10),
      hours:  parseFloat(document.getElementById('hoursSel').value  || '0'),
    };
  }

  function getUnitName(){
    const unit = document.getElementById('unitSel');
    const txt  = unit?.selectedOptions?.[0]?.textContent || '';
    return txt.split(' — ')[0].trim();
  }

  function getStartDT(){
    const v = document.getElementById('startInput').value;
    if(!v) return null;
    const dt = new Date(v);
    return isNaN(dt.getTime()) ? null : dt;
  }

  // localStorage helpers (tidak diubah)
  function loadTimers(){
    try { return JSON.parse(localStorage.getItem(KEY_TIMERS) || '[]'); }
    catch(e){ return []; }
  }
  function saveTimers(arr){
    localStorage.setItem(KEY_TIMERS, JSON.stringify(arr));
  }
  function saveTimer(){
    const unitName = getUnitName();
    const start    = getStartDT();
    const hours    = parseFloat(document.getElementById('hoursSel').value || '0');

    if (!unitName || !start || !(hours>0)) return;

    const endAt = new Date(start.getTime() + hours*60*60*1000).toISOString();
    const timers = loadTimers();

    timers.push({
      id: Date.now() + '-' + Math.random().toString(36).slice(2),
      unit: unitName,
      endAt: endAt,
      notified: false
    });

    const now = Date.now();
    const kept = timers.filter(t => (now - new Date(t.endAt).getTime()) < 24*3600*1000);
    saveTimers(kept);
  }

  let currentBill = 0;

  // fungsi bantu pembulatan half-hour: ceil ke 1000
  function roundToThousandCeil(n){
    return Math.ceil(n / 1000) * 1000;
  }

  function updateCalc(){
    const start = document.getElementById('startInput').value;
    const {base, extra, arcade, hours} = getNumbers();

    // update waktu selesai dengan presisi (handle fractional hours)
    const endLbl = document.getElementById('endLbl');
    try{
      if(start && hours>0){
        const dt = new Date(document.getElementById('startInput').value);
        dt.setTime(dt.getTime() + Math.round(hours * 3600 * 1000)); // hours to ms (works for 0.5)
        const hh = String(dt.getHours()).padStart(2,'0');
        const mm = String(dt.getMinutes()).padStart(2,'0');
        endLbl.textContent = hh + ':' + mm;
      } else {
        endLbl.textContent = '—:—';
      }
    }catch(e){ endLbl.textContent = '—:—'; }

    // Hitung tarif dasar dan tambahan
    const hourlyBase = base || 0;
    const extrasPerHour = (extra || 0) * EX_RATE;
    const arcadePerHour = (arcade || 0) * ARC_RATE;

    // Hitung bill: jika durasi 0.5 (30 menit) kita batasi pembulatan sesuai permintaan
    let rawBill = (hourlyBase + extrasPerHour + arcadePerHour) * (hours || 0);

    if (hours === 0.5) {
      // Untuk kasus 30 menit: hitung setengahnya lalu bulatkan ke atas ke kelipatan 1000
      const halfRaw = (hourlyBase + extrasPerHour + arcadePerHour) * 0.5;
      rawBill = roundToThousandCeil(halfRaw);
    } else {
      // untuk durasi >0.5 jam atau integer jam, biarkan sebagai hasil kali
      // jika perlu pembulatan ke integer rupiah: round ke angka terdekat
      rawBill = Math.round(rawBill);
    }

    currentBill = rawBill || 0;
    document.getElementById('billLbl').textContent = 'Rp ' + fmtIDR(currentBill);

    updateChange();
  }

  function updateChange(){
    const method = (document.getElementById('payMethod')?.value || 'Tunai').toLowerCase();
    const paid   = parseInt(document.getElementById('paidAmount')?.value || '0', 10);
    const change = method === 'tunai' ? Math.max(0, paid - (currentBill||0)) : 0;
    const out    = document.getElementById('changeLbl');
    if (out) out.value = 'Rp ' + fmtIDR(change);
  }

  // form submit check: jika tunai dan bayar kurang -> block
  const formEl = document.getElementById('fixedForm');
  if(formEl){
    formEl.addEventListener('submit', function(e){
      const method = (document.getElementById('payMethod')?.value || 'Tunai').toLowerCase();
      const paid   = parseInt(document.getElementById('paidAmount')?.value || '0', 10);

      if (method === 'tunai' && paid < currentBill){
        e.preventDefault();
        alert('Pembayaran tunai kurang dari total tagihan.');
        return;
      }

      try { saveTimer(); } catch(err){ /* ignore */ }
    });
  }

  // bind event listeners (re-bind existing code)
  ['unitSel','extraSel','arcadeSel','hoursSel','startInput'].forEach(id=>{
    const el=document.getElementById(id);
    if(el){ el.addEventListener('change', updateCalc); el.addEventListener('input', updateCalc); }
  });
  document.getElementById('payMethod')?.addEventListener('change', updateChange);
  document.getElementById('paidAmount')?.addEventListener('input', updateChange);

  // initial calc
  updateCalc();
})();
</script>
@endpush
