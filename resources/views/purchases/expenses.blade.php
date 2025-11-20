@extends('layouts.fixplay')

@section('page_title','Kasir Fixplay - Pengeluaran')

@section('page_content')
<h4 class="mb-3 text-dark fw-bold">Pengeluaran</h4>

<div class="row g-3">
  <div class="col-md-5">
    <div class="card card-dark">
      <div class="card-header">Catat Pengeluaran</div>
      <div class="card-body">
        <form method="post" action="{{ route('purchases.expenses.store') }}">
          @csrf
          <div class="mb-2">
            <label class="form-label">Kategori</label>
            <input name="category" class="form-control" placeholder="Listrik/Belanja Stok/..." required value="{{ old('category') }}">
          </div>
          <div class="mb-2">
            <label class="form-label">Deskripsi</label>
            <input name="description" class="form-control" value="{{ old('description') }}">
          </div>
          <div class="mb-2">
            <label class="form-label">Jumlah (Rp)</label>
            <input name="amount" type="number" class="form-control" value="{{ old('amount',0) }}" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Tanggal & Waktu (opsional)</label>
            <input name="timestamp" type="datetime-local" class="form-control" value="{{ old('timestamp') }}">
          </div>
          <button class="btn btn-primary">Simpan</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card card-dark">
      <div class="card-header">Riwayat</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm m-0 align-middle">
            <thead>
              <tr>
                <th>Waktu</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th class="text-end">Jumlah</th>
                <th class="text-end d-print-none"></th>
              </tr>
            </thead>
            <tbody>
              @forelse($items as $e)
              <tr>
                <td>{{ optional($e->timestamp)->format('d-m-Y H:i') ?? '-' }}</td>
                <td>{{ $e->category }}</td>
                <td>{{ $e->description }}</td>
                <td class="text-end">Rp {{ number_format($e->amount,0,',','.') }}</td>
                <td class="text-end d-print-none">
                  <button type="button" class="btn btn-sm btn-outline-secondary"
                          onclick="return editExpense({{ $e->id }}, '{{ addslashes($e->category) }}', '{{ addslashes($e->description ?: '') }}', {{ $e->amount }}, '{{ $e->timestamp ? $e->timestamp->format('Y-m-d\\TH:i') : '' }}')">
                    Edit
                  </button>

                  <form class="d-inline confirm-delete" method="post" action="{{ route('purchases.expenses.destroy', $e->id) }}"
                        onsubmit="return confirm('Hapus pengeluaran ini?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                  </form>
                </td>
              </tr>
              @empty
              <tr><td colspan="5" class="text-center text-muted p-3">Belum ada data.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="p-3">
          {{ $items->withQueryString()->links() }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function editExpense(id, category, description, amount, ts) {
  const newCat  = prompt("Kategori:", category || "");
  if (newCat === null) return false;

  const newDesc = prompt("Deskripsi:", description || "");
  if (newDesc === null) return false;

  const newAmt  = prompt("Jumlah (Rp):", amount);
  if (newAmt === null) return false;

  const newTs   = prompt("Waktu (YYYY-MM-DDTHH:MM):", ts || "");
  if (newTs === null) return false;

  const form = document.createElement('form');
  form.method = 'post';
  form.action = '/purchases/expenses/' + id;

  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const _token = document.createElement("input"); _token.type="hidden"; _token.name="_token"; _token.value=token; form.appendChild(_token);

  const _method = document.createElement("input"); _method.type="hidden"; _method.name="_method"; _method.value="PUT"; form.appendChild(_method);

  [['category', newCat], ['description', newDesc], ['amount', newAmt], ['timestamp', newTs]].forEach(function([k,v]){
    const i = document.createElement('input'); i.type='hidden'; i.name=k; i.value=v; form.appendChild(i);
  });

  document.body.appendChild(form); form.submit();
  return false;
}
</script>
@endpush
