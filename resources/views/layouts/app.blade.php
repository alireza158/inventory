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

    function gregorianToJalali(gregorianDate) {
      if (!gregorianDate) return '';

      const datePart = gregorianDate.split('T')[0] || '';
      const match = datePart.match(/^(\d{4})-(\d{2})-(\d{2})$/);
      if (!match) return '';

      const gy = Number(match[1]);
      const gm = Number(match[2]);
      const gd = Number(match[3]);

      return new persianDate()
        .toCalendar('gregorian')
        .parse(gy, gm, gd)
        .toCalendar('persian')
        .format('YYYY/MM/DD');
    }

    function jalaliToGregorian(jalaliDate) {
      if (!jalaliDate) return '';

      const match = jalaliDate.trim().match(/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/);
      if (!match) return '';

      const jy = Number(match[1]);
      const jm = Number(match[2]);
      const jd = Number(match[3]);

      return new persianDate([jy, jm, jd])
        .toCalendar('persian')
        .toCalendar('gregorian')
        .format('YYYY-MM-DD');
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

      $(el).persianDatepicker({
        autoClose: true,
        format: isDateTime ? 'YYYY/MM/DD HH:mm' : 'YYYY/MM/DD',
        timePicker: { enabled: isDateTime },
        calendar: { persian: { locale: 'fa' } },
        initialValue: false,
        persianDigit: false,
        toolbox: { calendarSwitch: { enabled: false } }
      });

      if (initialGregorian) {
        const jalali = gregorianToJalali(initialGregorian);
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
          const gregorianDate = jalaliToGregorian(datePart);
          if (!gregorianDate) return;

          dateInput.value = dateInput.dataset.faOriginalType === 'datetime-local' && timePart
            ? `${gregorianDate}T${timePart}`
            : gregorianDate;
        });
      });
    }

    $('input[type="date"], input[type="datetime-local"]').each(function(){
      this.dataset.faOriginalType = this.type;
      bindDateInput(this);
    });
  }

  document.addEventListener('DOMContentLoaded', initPersianDatepickers);
</script>

@stack('scripts')
</body>
</html>
