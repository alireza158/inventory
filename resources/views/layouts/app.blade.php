<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'سیستم انبار آریا جانبی') }}</title>
    <script src="{{ asset('lib/jquery.min.js') }}"></script>

    <!-- اگر select2 داری -->
    <script src="{{ asset('lib/select2.min.js') }}"></script>

    <!-- Jalali Datepicker -->
    <link rel="stylesheet" href="{{ asset('lib/jalalidatepicker.min.css') }}">
    <script src="{{ asset('lib/jalalidatepicker.min.js') }}"></script>

    <!-- Bootstrap (اختیاری، فقط اگر نیاز داری) -->
    <script src="{{ asset('lib/bootstrap.bundle.min.js') }}"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

</head>
<style>
    body, button, input, select, textarea {
      font-family: "Vazirmatn", system-ui, -apple-system, "Segoe UI", Tahoma, Arial, sans-serif !important;
    }
  </style>

<body class="bg-light">
<div class="d-flex" style="min-height: 100vh">

    {{-- Sidebar --}}
    @include('layouts.sidebar')

    {{-- Main --}}
    <div class="flex-grow-1">
        {{-- Topbar --}}
        <div class="bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2 fw-bold text-muted">
                <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" style="height: 34px; width: 34px; object-fit: contain;">
                <span>{{ config('app.name','سیستم انبار آریا جانبی') }}</span>
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
  function initJalaliDatepickers(){
    if (!window.jalaliDatepicker) return;

    function div(a, b) { return ~~(a / b); }
    function pad(v) { return String(v).padStart(2, '0'); }

    function gregorianToJalali(gy, gm, gd) {
      const g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
      let jy = (gy <= 1600) ? 0 : 979;
      gy -= (gy <= 1600) ? 621 : 1600;
      const gy2 = (gm > 2) ? (gy + 1) : gy;
      let days = (365 * gy) + div(gy2 + 3, 4) - div(gy2 + 99, 100) + div(gy2 + 399, 400) - 80 + gd + g_d_m[gm - 1];
      jy += 33 * div(days, 12053);
      days %= 12053;
      jy += 4 * div(days, 1461);
      days %= 1461;
      if (days > 365) {
        jy += div(days - 1, 365);
        days = (days - 1) % 365;
      }
      const jm = (days < 186) ? 1 + div(days, 31) : 7 + div(days - 186, 30);
      const jd = 1 + ((days < 186) ? (days % 31) : ((days - 186) % 30));
      return [jy, jm, jd];
    }

    function jalaliToGregorian(jy, jm, jd) {
      jy += 1595;
      let days = -355668 + (365 * jy) + div(jy, 33) * 8 + div((jy % 33) + 3, 4) + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
      let gy = 400 * div(days, 146097);
      days %= 146097;
      if (days > 36524) {
        gy += 100 * div(--days, 36524);
        days %= 36524;
        if (days >= 365) days++;
      }
      gy += 4 * div(days, 1461);
      days %= 1461;
      if (days > 365) {
        gy += div(days - 1, 365);
        days = (days - 1) % 365;
      }
      let gd = days + 1;
      const sal_a = [0,31,((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28,31,30,31,30,31,31,30,31,30,31];
      let gm = 0;
      for (gm = 1; gm <= 12 && gd > sal_a[gm]; gm++) gd -= sal_a[gm];
      return [gy, gm, gd];
    }

    function gregorianStringToJalali(str) {
      const datePart = (str || '').split('T')[0] || '';
      const m = datePart.match(/^(\d{4})-(\d{2})-(\d{2})$/);
      if (!m) return '';
      const j = gregorianToJalali(Number(m[1]), Number(m[2]), Number(m[3]));
      return `${j[0]}/${pad(j[1])}/${pad(j[2])}`;
    }

    function jalaliStringToGregorian(str) {
      const m = (str || '').trim().match(/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/);
      if (!m) return '';
      const g = jalaliToGregorian(Number(m[1]), Number(m[2]), Number(m[3]));
      return `${g[0]}-${pad(g[1])}-${pad(g[2])}`;
    }

    function bindDateInput(el){
      if (el.dataset.faDateBound === '1') return;
      el.dataset.faDateBound = '1';

      const isDateTime = el.type === 'datetime-local';
      const initialGregorian = el.value || '';
      const initialTime = isDateTime && initialGregorian.includes('T')
        ? initialGregorian.split('T')[1].slice(0,5)
        : '';

      el.type = 'text';
      el.setAttribute('autocomplete', 'off');
      el.setAttribute('dir', 'ltr');

      el.setAttribute('data-jdp', '');
      if (!isDateTime) el.setAttribute('data-jdp-only-date', '');

      if (initialGregorian) {
        const jalali = gregorianStringToJalali(initialGregorian);
        el.value = isDateTime ? `${jalali} ${initialTime}`.trim() : jalali;
      }

      const form = el.closest('form');
      if (!form || form.dataset.faDateSubmitBound === '1') return;
      form.dataset.faDateSubmitBound = '1';

      form.addEventListener('submit', function(){
        form.querySelectorAll('input[data-fa-date-bound="1"]').forEach(function(dateInput){
          const raw = (dateInput.value || '').trim();
          if (!raw) return;

          const [datePart, timePart] = raw.split(' ');
          const gregorianDate = jalaliStringToGregorian(datePart);
          if (!gregorianDate) return;

          dateInput.value = dateInput.dataset.faOriginalType === 'datetime-local' && timePart
            ? `${gregorianDate}T${timePart}`
            : gregorianDate;
        });
      });
    }

    document.querySelectorAll('input[type="date"], input[type="datetime-local"]').forEach(function(el){
      el.dataset.faOriginalType = el.type;
      bindDateInput(el);
    });

    jalaliDatepicker.startWatch({
      minDate: 'attr',
      maxDate: 'attr',
      time: true
    });
  }

  document.addEventListener('DOMContentLoaded', initJalaliDatepickers);
</script>

@stack('scripts')
</body>
</html>
