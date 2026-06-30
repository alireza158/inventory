@extends('layouts.app')

@section('title', 'گزارشات مالی')
@section('content_class', 'app-content-wide')

@section('content')
<style>
  .finance-reports-page{max-width:1100px; margin:0 auto;}
  .reports-hero,.report-card{border:0; border-radius:20px; box-shadow:0 10px 28px rgba(15,23,42,.06);}
  .reports-hero{background:linear-gradient(135deg,#fff,#f8fafc); padding:22px;}
  .report-card{height:100%; background:#fff; transition:transform .18s ease, box-shadow .18s ease; text-decoration:none; color:inherit; display:block;}
  .report-card:hover{transform:translateY(-3px); box-shadow:0 16px 34px rgba(15,23,42,.1); color:inherit;}
  .report-icon{width:52px; height:52px; display:flex; align-items:center; justify-content:center; border-radius:16px; background:#eef2ff; font-size:1.6rem;}
</style>

<div class="finance-reports-page">
  <div class="reports-hero mb-4">
    <div class="h4 fw-black mb-1">گزارشات مالی</div>
    <div class="text-muted small">گزارش‌های مالی در قالب کارت نمایش داده می‌شوند و امکان افزودن گزارش‌های جدید در همین صفحه وجود دارد.</div>
  </div>

  <div class="row g-3">
    @foreach($reports as $report)
      <div class="col-md-6 col-xl-4">
        <a class="report-card p-4" href="{{ $report['active'] ? $report['route'] : '#' }}">
          <div class="d-flex gap-3 align-items-start">
            <div class="report-icon">{{ $report['icon'] }}</div>
            <div>
              <div class="fw-black mb-2">{{ $report['title'] }}</div>
              <div class="text-muted small lh-lg">{{ $report['description'] }}</div>
              <div class="mt-3"><span class="badge text-bg-success">فعال</span></div>
            </div>
          </div>
        </a>
      </div>
    @endforeach
  </div>
</div>
@endsection
