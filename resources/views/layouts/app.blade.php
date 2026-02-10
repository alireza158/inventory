<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'انبارداری') }}</title>
    <script src="{{ asset('lib/jquery.min.js') }}"></script>

    <!-- اگر select2 داری -->
    <script src="{{ asset('lib/select2.min.js') }}"></script>

    <!-- وابستگی اصلی دیت‌پیکر -->
    <script src="{{ asset('lib/persian-date.min.js') }}"></script>
    <script src="{{ asset('lib/persian-datepicker.min.js') }}"></script>

    <!-- Bootstrap (اختیاری، فقط اگر نیاز داری) -->
    <script src="{{ asset('lib/bootstrap.bundle.min.js') }}"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>

<body class="bg-light">
<div class="d-flex" style="min-height: 100vh">

    {{-- Sidebar --}}
    @include('layouts.sidebar')

    {{-- Main --}}
    <div class="flex-grow-1">
        {{-- Topbar --}}
        <div class="bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
            <div class="fw-bold text-muted">
                {{ config('app.name','انبارداری') }}
            </div>

            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    {{ auth()->user()->name ?? 'کاربر' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->email ?? '' }}</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger">خروج</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        <main class="container py-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-bold mb-2">خطاها:</div>
                    <ul class="mb-0">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

</div>


{{-- فرمت هزارگان برای ورودی‌های money --}}
<script>
  function formatMoneyInput(el){
    const raw = (el.value || '').replace(/[^\d]/g,'');
    el.value = raw.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }
  document.addEventListener('input', function(e){
    if(e.target && e.target.classList.contains('money')) formatMoneyInput(e.target);
  });
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('input.money').forEach(formatMoneyInput);
  });
</script>


<script>
  function initPersianDatepickers(){
    if (!window.jQuery || !$.fn.persianDatepicker || !window.persianDate) return;

    $('input[type="date"]').each(function(){
      const el = this;
      if (el.dataset.faDateBound === '1') return;
      el.dataset.faDateBound = '1';

      const currentValue = el.value || '';
      el.type = 'text';
      el.setAttribute('autocomplete', 'off');

      $(el).persianDatepicker({
        autoClose: true,
        format: 'YYYY/MM/DD',
        initialValueType: 'gregorian',
        persianDigit: false,
        formatter: function(unixDate){
          if (!unixDate) return '';
          return new persianDate(unixDate).toCalendar('gregorian').format('YYYY-MM-DD');
        }
      });

      if (currentValue) {
        el.value = currentValue;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', initPersianDatepickers);
</script>

@stack('scripts')
</body>
</html>
