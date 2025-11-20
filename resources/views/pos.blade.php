@extends('layouts.fixplay')

@section('page_title','Kasir Fixplay - Penjualan')

@section('page_content')
<h4 class="mb-3 text-dark fw-bold">Penjualan Produk</h4>
<div class="card">
  <div class="card-body">
    {{-- Alert Modal --}}
    <div class="modal fade fixplay-alert" id="posAlert" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header border-0">
            <h5 class="modal-title">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              Transaksi Ditolak
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body" id="posAlertBody">Pesan</div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK, Mengerti</button>
          </div>
        </div>
      </div>
    </div>

    <style>
    .fixplay-alert .modal-content{
      background:
        radial-gradient(100% 140% at 0% 0%, rgba(124,58,237,.25), transparent 40%),
        radial-gradient(120% 120% at 100% 0%, rgba(59,130,246,.22), transparent 45%),
        linear-gradient(180deg, #151528, #0f1020);
      color:#eef2ff;
      border:1px solid rgba(122,92,255,.45);
      box-shadow: 0 10px 30px rgba(0,0,0,.45), 0 0 24px rgba(124,58,237,.3);
      border-radius:14px;
    }
    .fixplay-alert .modal-header .modal-title{ font-weight:800; text-shadow:0 0 12px rgba(124,58,237,.45); }
    .fixplay-alert .btn-primary{
      background: linear-gradient(90deg, #7a5cff, #38bdf8); border:none; color:#0a0a12; font-weight:700;
      box-shadow:0 6px 18px rgba(122,92,255,.35);
    }
    </style>

    <form method="post" action="{{ route('pos.checkout') }}" id="pos-form">
      @csrf
      <div id="rows"></div>

      <div class="d-flex gap-2 mt-2 align-items-center">
        <button class="btn btn-outline-primary" type="button" onclick="addRow()">+ Tambah Item</button>
        <div class="ms-auto fw-bold">Total: <span id="totalLbl">Rp 0</span></div>
      </div>

      <div class="row mt-3 g-2">
        <div class="col-md-3">
          <label class="form-label">Metode Bayar</label>
          <select name="payment_method" id="payMethod" class="form-select">
            <option value="Tunai">Tunai</option>
            <option value="QRIS">QRIS</option>
            <option value="Transfer">Transfer</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Dibayar</label>
          <input type="number" class="form-control" name="paid_amount" id="paidAmount" value="0" min="0">
        </div>
        <div class="col-md-3">
          <label class="form-label">Kembalian</label>
          <input type="text" class="form-control bg-white text-dark" id="changeLbl" value="Rp 0" disabled>
        </div>
      </div>

      <div class="mt-3">
        <label class="form-label">Catatan (opsional)</label>
        <input name="note" class="form-control" placeholder="mis. bayar tunai, diskon, dll.">
      </div>

      <button class="btn btn-success mt-3">Checkout</button>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script id="pos-data" type="application/json">@json($products)</script>
<script>
(function(){
  const posProducts = JSON.parse(document.getElementById('pos-data').textContent || '[]');
  function fmtIDR(n){ return (n||0).toLocaleString('id-ID'); }

  function rowTemplate(idx){
    return `
    <div class="row g-2 align-items-end pos-row" data-idx="${idx}">
      <div class="col-md-6">
        <label class="form-label">Produk</label>
        <select class="form-select prod" name="product_id[]">
          <option value="">-- pilih --</option>
          ${posProducts.map(p => `
            <option value="${p.id}" data-price="${p.price}" data-stock="${p.stock}">
              ${p.name} (Rp ${fmtIDR(p.price)}) — stok: ${p.stock}
            </option>`).join('')}
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Qty</label>
        <input type="number" class="form-control qty" name="qty[]" value="1" min="1">
      </div>
      <div class="col-md-2">
        <label class="form-label">Subtotal</label>
        <input type="text" class="form-control subtotal bg-white text-dark" value="Rp 0" disabled>
      </div>
      <div class="col-md-2">
        <button class="btn btn-outline-danger w-100 remove">Hapus</button>
      </div>
    </div>`;
  }

  let idx=0, currentTotal=0;
  function addRow(){
    const c=document.getElementById('rows');
    c.insertAdjacentHTML('beforeend', rowTemplate(idx++));
    bind(); updateTotals();
  }
  function bind(){
    document.querySelectorAll('.pos-row').forEach(row=>{
      const prod=row.querySelector('.prod');
      const qty =row.querySelector('.qty');
      const rm  =row.querySelector('.remove');
      prod.onchange=()=>{ updateTotals(); validateRowStock(row); };
      qty.oninput  =()=>{ updateTotals(); validateRowStock(row); };
      rm.onclick=(e)=>{ e.preventDefault(); row.remove(); updateTotals(); };
    });
  }

  function validateRowStock(row){
    const sel = row.querySelector('.prod');
    const pid = sel.value;
    const qty = parseInt(row.querySelector('.qty').value||"0",10);
    const stock = parseInt(sel.selectedOptions[0]?.dataset?.stock || "0", 10);
    const invalid = pid && qty>stock;
    row.querySelector('.qty').classList.toggle('is-invalid', invalid);
    return invalid;
  }

  function findStockError(){
    let msg=null;
    document.querySelectorAll('.pos-row').forEach(row=>{
      if (msg) return;
      const sel=row.querySelector('.prod');
      const txt=sel.selectedOptions[0]?.text || '';
      const name=txt.split(' (')[0] || 'Produk';
      const qty =parseInt(row.querySelector('.qty').value||"0",10);
      const stock=parseInt(sel.selectedOptions[0]?.dataset?.stock || "0",10);
      if (sel.value && qty>stock){
        msg = `Stok ${name} tidak cukup.\nStok tersedia: ${stock.toLocaleString('id-ID')}, diminta: ${qty.toLocaleString('id-ID')}.\n\nTransaksi dibatalkan.`;
      }
    });
    return msg;
  }

  function updateTotals(){
    let total=0;
    document.querySelectorAll('.pos-row').forEach(row=>{
      const prod=row.querySelector('.prod');
      const qty =parseInt(row.querySelector('.qty').value||"0",10);
      const price=parseInt(prod.selectedOptions[0]?.dataset?.price||"0",10);
      const sub=price*(qty>0?qty:0);
      total+=sub;
      row.querySelector('.subtotal').value="Rp "+fmtIDR(sub);
    });
    currentTotal=total;
    document.getElementById('totalLbl').textContent="Rp "+fmtIDR(total);
    updateChange();
  }

  function updateChange(){
    const method=(document.getElementById('payMethod')?.value||'Tunai').toLowerCase();
    const paid  =parseInt(document.getElementById('paidAmount')?.value||"0",10);
    const change=(method==='tunai') ? Math.max(0, paid-(currentTotal||0)) : 0;
    document.getElementById('changeLbl').value="Rp "+fmtIDR(change);
  }

  document.addEventListener('input',(e)=>{ if(e.target?.id==='paidAmount') updateChange(); });
  document.addEventListener('change',(e)=>{ if(e.target?.id==='payMethod') updateChange(); });

  const posAlertEl = document.getElementById('posAlert');
  const posAlert   = posAlertEl ? new bootstrap.Modal(posAlertEl, {backdrop:'static'}) : null;
  function showAlert(msg){
    const body=document.getElementById('posAlertBody');
    if(body) body.textContent = msg;
    if(posAlert) posAlert.show(); else alert(msg);
  }

  const form=document.getElementById('pos-form');
  form.addEventListener('submit', function(e){
    const stockMsg = findStockError();
    if (stockMsg){
      e.preventDefault(); e.stopPropagation();
      showAlert(stockMsg);
      return false;
    }
    const paid  =parseInt(document.getElementById('paidAmount')?.value||"0",10);
    if (paid < (currentTotal||0)){
      e.preventDefault(); e.stopPropagation();
      showAlert(
        "Nominal dibayar kurang.\n" +
        "Total: Rp " + (currentTotal||0).toLocaleString('id-ID') + "\n" +
        "Dibayar: Rp " + (paid||0).toLocaleString('id-ID')
      );
      return false;
    }
  });

  window.addRow=addRow;
  addRow();
})();
</script>
@endpush
