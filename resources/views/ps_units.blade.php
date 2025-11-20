@extends('layouts.fixplay')

@section('page_title', 'Kasir Fixplay - Unit PS')

@section('page_content')
<h4 class="mb-3 text-dark fw-bold">Unit PS</h4>

<div class="row g-3">
  <!-- Kiri: form tambah unit PS -->
  <div class="col-md-5">
    <div class="card card-dark">
      <div class="card-header">Tambah Unit PS</div>
      <div class="card-body">
        <form method="post" action="{{ route('ps_units.store') }}">
          @csrf
          <div class="mb-2">
            <label class="form-label">Nama Unit (mis. PS 4 - A)</label>
            <input name="name" class="form-control" required value="{{ old('name') }}">
          </div>
          <div class="mb-2">
            <label class="form-label">Tarif per Jam (Rp)</label>
            <input name="hourly_rate" type="number" class="form-control" value="{{ old('hourly_rate') }}" required>
          </div>
          <div class="mb-2 form-check">
            <input type="checkbox" name="is_active" value="1" id="activeCheck" class="form-check-input" {{ old('is_active',1) ? 'checked' : '' }}>
            <label for="activeCheck" class="form-check-label">Aktif</label>
          </div>
          <button class="btn btn-primary">Simpan</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Kanan: daftar unit -->
  <div class="col-md-7">
    <div class="card card-dark">
      <div class="card-header">Daftar Unit PS</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover m-0 align-middle">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Tarif/Jam</th>
                <th class="text-end d-print-none">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($units as $u)
              <tr>
                <td>
                  {{ $u->name }}
                  @if(!$u->is_active)
                    <span class="badge bg-warning text-dark ms-2">Nonaktif</span>
                  @endif
                </td>
                <td>Rp {{ number_format($u->hourly_rate ?? 0,0,',','.') }}</td>
                <td class="text-end d-print-none">
                  <button class="btn btn-sm btn-outline-secondary"
                          onclick="return editUnit({{ $u->id }}, '{{ addslashes($u->name) }}', {{ $u->hourly_rate ?? 0 }})">Edit</button>

                  <form class="d-inline" method="post" action="{{ route('ps_units.toggle', $u->id) }}">
                    @csrf
                    <button class="btn btn-sm {{ $u->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                      {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                  </form>

                  <form class="d-inline confirm-delete" method="post" action="{{ route('ps_units.destroy', $u->id) }}" onsubmit="return confirm('Hapus unit PS?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                  </form>
                </td>
              </tr>
              @empty
              <tr><td colspan="3" class="text-center text-muted p-3">Belum ada unit.</td></tr>
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
function editUnit(id, name, rate){
  const newName = prompt("Nama Unit:", name);
  if (newName === null) return false;
  const newRate = prompt("Tarif/Jam (Rp):", rate);
  if (newRate === null) return false;

  // buat form update dinamis (PUT)
  const form = document.createElement("form");
  form.method = "post";
  form.action = "/ps-units/" + id;
  // csrf token
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const _token = document.createElement("input"); _token.type="hidden"; _token.name="_token"; _token.value=token; form.appendChild(_token);
  // spoof PUT
  const _method = document.createElement("input"); _method.type="hidden"; _method.name="_method"; _method.value="PUT"; form.appendChild(_method);

  [["name", newName], ["hourly_rate", newRate]].forEach(([k,v])=>{
    const i=document.createElement("input"); i.type="hidden"; i.name=k; i.value=v; form.appendChild(i);
  });

  document.body.appendChild(form);
  form.submit();
  return false;
}
</script>
@endpush
