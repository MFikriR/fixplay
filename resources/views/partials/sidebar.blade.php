<div class="brand mb-3">
  <span class="fw-bold fs-4 text-white">FIXPLAY</span>
</div>

<nav class="menu">
  <a href="{{ route('dashboard') }}"
     class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
    <i class="bi bi-house"></i> <span>Beranda</span>
  </a>

  <a href="{{ url('/sessions') }}" class="{{ request()->is('sessions*') ? 'active' : '' }}">
    <i class="bi bi-controller"></i> <span>Rental</span>
  </a>

  <a href="{{ url('/ps-units') }}" class="{{ request()->is('ps-units*') ? 'active' : '' }}">
    <i class="bi bi-laptop"></i> <span>Unit PS</span>
  </a>

  <a href="{{ route('pos.index') }}" class="{{ request()->is('pos*') ? 'active' : '' }}">
    <i class="bi bi-cup-straw"></i> <span>Makanan</span>
  </a>

  <a href="{{ url('/products') }}" class="{{ request()->is('products*') ? 'active' : '' }}">
    <i class="bi bi-box"></i> <span>Stok</span>
  </a>

  <a href="{{ url('/purchases/expenses') }}" class="{{ request()->is('purchases*') ? 'active' : '' }}">
    <i class="bi bi-bag-plus"></i> <span>Beli Stock</span>
  </a>

  <a href="{{ url('/reports') }}" class="{{ request()->is('reports*') ? 'active' : '' }}">
    <i class="bi bi-graph-up-arrow"></i> <span>Laporan</span>
  </a>
</nav>
