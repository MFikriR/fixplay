<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Fixplay')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Bootstrap + Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  @stack('styles')
  <!-- Tema Fixplay (Opsi A: ambil dari public/css) -->
  <link rel="stylesheet" href="{{ asset('css/fixplay.css') }}">
</head>
<body>
  {{-- Navbar sederhana, ganti sesuai kebutuhan --}}
  <nav class="navbar navbar-expand-lg border-bottom mb-3">
    <div class="container">
      <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">Fixplay</a>
      <ul class="navbar-nav ms-3">
        <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('products.index') }}"><i class="bi bi-bag me-1"></i>Produk</a></li>
      </ul>
    </div>
  </nav>

  <div class="container mb-5">
    @yield('content')
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  @stack('scripts')
</body>
</html>
