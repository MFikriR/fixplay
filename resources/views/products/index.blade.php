@extends('layouts.fixplay')

@section('page_title','Kasir Fixplay - Produk')

@section('page_content')
<div class="d-flex align-items-center justify-content-between mb-2">
  <h4 class="mb-3 text-dark fw-bold">Produk</h4>

  <form class="d-flex" method="get" action="{{ route('products.index') }}">
    <input class="form-control me-2" type="search" placeholder="Cari nama/kategori" name="q" value="{{ request('q') }}">
    <button class="btn btn-outline-primary text-dark" type="submit">Cari</button>
  </form>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="card card-dark">
      <div class="card-header">Tambah Produk</div>
      <div class="card-body">
        <form method="post" action="{{ route('products.store') }}">
          @csrf
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Nama</label>
              <input name="name" class="form-control" required value="{{ old('name') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Kategori</label>
              <input name="category" class="form-control" placeholder="Makanan/Minuman/Cemilan" value="{{ old('category') }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Harga (Rp)</label>
              <input name="price" type="number" class="form-control" value="{{ old('price',0) }}" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Stok</label>
              <input name="stock" type="number" class="form-control" value="{{ old('stock',0) }}" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Satuan</label>
              <input name="unit" class="form-control" value="{{ old('unit','pcs') }}">
            </div>
          </div>

          <div class="mt-3">
            <button class="btn btn-primary">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card card-dark">
      <div class="card-header">Daftar Produk</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle m-0">
            <thead>
              <tr>
                <th>Nama</th><th>Kategori</th><th class="text-end">Harga</th><th class="text-end">Stok</th><th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($items as $p)
              <tr>
                <td>{{ $p->name }}</td>
                <td>{{ $p->category }}</td>
                <td class="text-end">Rp {{ number_format($p->price ?? 0,0,',','.') }}</td>
                <td class="text-end">{{ number_format($p->stock ?? 0,0,',','.') }} {{ $p->unit }}</td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-secondary"
                          onclick="return editProduct({{ $p->id }}, '{{ addslashes($p->name) }}', '{{ addslashes($p->category) }}', {{ $p->price }}, {{ $p->stock }}, '{{ addslashes($p->unit) }}')">
                    Edit
                  </button>

                  <form class="d-inline confirm-delete" method="post" action="{{ route('products.destroy', $p->id) }}" onsubmit="return confirm('Hapus produk?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                  </form>
                </td>
              </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted p-3">Belum ada produk.</td></tr>
              @endforelse
            </tbody>
          </table>

          <div class="p-3">
            {{ $items->withQueryString()->links() }} {{-- pagination --}}
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function editProduct(id, name, category, price, stock, unit) {
  const newName = prompt("Nama:", name);
  if (newName === null) return false;
  const newCat = prompt("Kategori:", category || "");
  if (newCat === null) return false;
  const newPrice = prompt("Harga (Rp):", price);
  if (newPrice === null) return false;
  const newStock = prompt("Stok:", stock);
  if (newStock === null) return false;
  const newUnit = prompt("Satuan:", unit || "pcs");
  if (newUnit === null) return false;

  // buat form update dinamis
  const form = document.createElement("form");
  form.method = "post";
  form.action = "/products/" + id;

  // CSRF token
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const _token = document.createElement("input"); _token.type="hidden"; _token.name="_token"; _token.value=token; form.appendChild(_token);

  // spoof PUT
  const _method = document.createElement("input"); _method.type="hidden"; _method.name="_method"; _method.value="PUT"; form.appendChild(_method);

  [["name", newName], ["category", newCat], ["price", newPrice], ["stock", newStock], ["unit", newUnit]]
    .forEach(([k, v]) => {
      const input = document.createElement("input");
      input.type = "hidden";
      input.name = k;
      input.value = v;
      form.appendChild(input);
    });

  document.body.appendChild(form);
  form.submit();
  return false;
}
</script>
@endpush
