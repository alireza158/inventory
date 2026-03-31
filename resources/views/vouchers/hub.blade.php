@extends('layouts.app')

@section('content')
<style>
    :root{
        --brd:#e8edf3;
        --soft:#f8fafc;
        --soft2:#f3f6fb;
        --text:#0f172a;
        --muted:#64748b;
        --blue:#2563eb;
        --blue-soft:#eff6ff;
        --green-soft:#ecfdf5;
        --orange-soft:#fff7ed;
        --violet-soft:#f5f3ff;
        --shadow:0 14px 34px rgba(15,23,42,.06);
    }

    .voucher-home-wrap{
        padding: 8px 0 28px;
    }

    .hero-box{
        border:1px solid var(--brd);
        border-radius:24px;
        background:linear-gradient(135deg,#ffffff,#f8fbff 55%,#eef6ff);
        box-shadow:var(--shadow);
        overflow:hidden;
    }

    .hero-title{
        font-size:30px;
        font-weight:900;
        color:var(--text);
        margin-bottom:8px;
    }

    .hero-sub{
        color:var(--muted);
        font-size:14px;
        line-height:1.9;
        max-width:760px;
        margin-bottom:0;
    }

    .soft-chip{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:8px 12px;
        border-radius:999px;
        border:1px solid var(--brd);
        background:#fff;
        font-size:12px;
        font-weight:700;
        color:var(--text);
    }

    .voucher-card{
        display:block;
        text-decoration:none;
        border:1px solid var(--brd);
        border-radius:22px;
        background:#fff;
        box-shadow:var(--shadow);
        padding:18px;
        height:100%;
        transition:.18s ease;
        position:relative;
        overflow:hidden;
    }

    .voucher-card:hover{
        transform:translateY(-3px);
        border-color:#bfdbfe;
        box-shadow:0 18px 40px rgba(37,99,235,.10);
    }

    .voucher-card::after{
        content:"";
        position:absolute;
        inset-inline-end:-40px;
        top:-40px;
        width:120px;
        height:120px;
        border-radius:999px;
        background:rgba(255,255,255,.35);
        pointer-events:none;
    }

    .voucher-card.return-sale{ background:linear-gradient(135deg,#ffffff,#fffaf5); }
    .voucher-card.scrap{ background:linear-gradient(135deg,#ffffff,#fff9fb); }
    .voucher-card.personnel{ background:linear-gradient(135deg,#ffffff,#f7fbff); }
    .voucher-card.transfer{ background:linear-gradient(135deg,#ffffff,#f5fffb); }

    .voucher-icon{
        width:56px;
        height:56px;
        border-radius:16px;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:28px;
        margin-bottom:14px;
        border:1px solid rgba(255,255,255,.5);
    }

    .voucher-icon.return-sale{ background:var(--orange-soft); }
    .voucher-icon.scrap{ background:#fef2f2; }
    .voucher-icon.personnel{ background:var(--blue-soft); }
    .voucher-icon.transfer{ background:var(--green-soft); }

    .voucher-title{
        font-size:18px;
        font-weight:900;
        color:var(--text);
        margin-bottom:6px;
    }

    .voucher-desc{
        color:var(--muted);
        font-size:13px;
        line-height:1.9;
        margin-bottom:14px;
        min-height:48px;
    }

    .voucher-meta{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
    }

    .meta-pill{
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:6px 10px;
        border-radius:999px;
        background:rgba(255,255,255,.8);
        border:1px solid var(--brd);
        color:#334155;
        font-size:12px;
        font-weight:700;
    }

    .panel-card{
        border:1px solid var(--brd);
        border-radius:22px;
        background:#fff;
        box-shadow:var(--shadow);
        overflow:hidden;
        height:100%;
    }

    .panel-head{
        padding:14px 18px;
        border-bottom:1px solid var(--brd);
        background:linear-gradient(0deg,#fff,var(--soft2));
    }

    .panel-title{
        font-size:15px;
        font-weight:900;
        margin:0;
        color:var(--text);
    }

    .panel-sub{
        font-size:12px;
        color:var(--muted);
        margin:4px 0 0;
    }

    .steps-list{
        margin:0;
        padding-right:18px;
    }

    .steps-list li{
        color:#334155;
        margin-bottom:10px;
        line-height:1.9;
        font-size:13px;
    }

    .outputs-box{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:16px;
        flex-wrap:wrap;
        padding:18px;
        border-radius:20px;
        background:linear-gradient(135deg,#0f172a,#1e293b);
        color:#fff;
        box-shadow:var(--shadow);
    }

    .outputs-title{
        font-size:18px;
        font-weight:900;
        margin-bottom:4px;
    }

    .outputs-sub{
        font-size:13px;
        color:rgba(255,255,255,.78);
        margin:0;
        line-height:1.9;
    }

    .outputs-btn{
        border-radius:14px;
        padding:.85rem 1.1rem;
        font-weight:800;
        white-space:nowrap;
    }

    .mini-note{
        border:1px dashed #dbeafe;
        background:#f8fbff;
        border-radius:16px;
        padding:12px 14px;
        color:#334155;
        font-size:13px;
        line-height:1.9;
    }
</style>

<div class="container voucher-home-wrap">
    <div class="hero-box mb-4">
        <div class="p-4 p-lg-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <div class="hero-title">حواله</div>
                    <p class="hero-sub">
                        از این بخش می‌توانی سریع نوع حواله را انتخاب کنی، حواله جدید ثبت کنی و بعداً روی لیست حواله‌ها گزارش‌گیری داشته باشی.
                        ساختار صفحه طوری چیده شده که کاربر بدون سردرگمی مستقیم وارد فرآیند درست شود.
                    </p>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="soft-chip">ثبت سریع</span>
                        <span class="soft-chip">دسترسی راحت‌تر</span>
                        <span class="soft-chip">آماده برای گزارش‌گیری</span>
                    </div>
                </div>

                <div>
                    <a class="btn btn-primary px-4 py-2 rounded-4 fw-bold" href="{{ route('warehouse.outputs') }}">
                        مشاهده خروجی‌های انبار
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <a class="voucher-card return-sale" href="{{ route('vouchers.section.index', 'return-from-sale') }}">
                <div class="voucher-icon return-sale">🔁</div>
                <div class="voucher-title">ثبت برگشت از فروش</div>
                <div class="voucher-desc">
                    ثبت و پیگیری حواله‌های مرجوعی مشتری بر اساس فروش‌های ثبت‌شده.
                </div>
                <div class="voucher-meta">
                    <span class="meta-pill">مشتری</span>
                    <span class="meta-pill">فاکتور فروش</span>
                    <span class="meta-pill">مرجوعی</span>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-3">
            <a class="voucher-card scrap" href="{{ route('vouchers.section.index', 'scrap') }}">
                <div class="voucher-icon scrap">🗑️</div>
                <div class="voucher-title">ثبت انبار ضایعات</div>
                <div class="voucher-desc">
                    ثبت و مشاهده حواله‌های مربوط به کالاهای ضایعاتی یا خارج‌شده از چرخه مصرف.
                </div>
                <div class="voucher-meta">
                    <span class="meta-pill">ضایعات</span>
                    <span class="meta-pill">ثبت خروج</span>
                    <span class="meta-pill">کنترل موجودی</span>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-3">
            <a class="voucher-card personnel" href="{{ route('vouchers.section.index', 'personnel') }}">
                <div class="voucher-icon personnel">👤</div>
                <div class="voucher-title">ثبت حواله پرسنل</div>
                <div class="voucher-desc">
                    تحویل کالا یا تجهیزات به پرسنل و ثبت سابقه اموال تحویلی به هر نفر.
                </div>
                <div class="voucher-meta">
                    <span class="meta-pill">پرسنل</span>
                    <span class="meta-pill">اموال</span>
                    <span class="meta-pill">سابقه تحویل</span>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-3">
            <a class="voucher-card transfer" href="{{ route('vouchers.section.index', 'transfer') }}">
                <div class="voucher-icon transfer">🚚</div>
                <div class="voucher-title">ثبت حواله بین انباری</div>
                <div class="voucher-desc">
                    انتقال کالا بین انبارها با مسیر مشخص، ثبت مقصد و نگهداری سابقه جابه‌جایی.
                </div>
                <div class="voucher-meta">
                    <span class="meta-pill">انتقال</span>
                    <span class="meta-pill">مبدا/مقصد</span>
                    <span class="meta-pill">رهگیری</span>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="panel-card">
                <div class="panel-head">
                    <h6 class="panel-title">راهنمای سریع کار با حواله‌ها</h6>
                    <p class="panel-sub">برای استفاده راحت‌تر، مسیر کار هر نوع حواله را کوتاه و واضح نگه دار</p>
                </div>

                <div class="p-3 p-lg-4">
                    <ol class="steps-list">
                        <li>اول نوع حواله را از کارت‌های بالا انتخاب کن.</li>
                        <li>اطلاعات اصلی مثل کالا، مبدا، مقصد یا شخص مرتبط را ثبت کن.</li>
                        <li>قبل از تایید، تعداد و اقلام را دوباره بررسی کن.</li>
                        <li>برای پیگیری‌های بعدی از بخش خروجی‌های انبار یا لیست حواله‌ها استفاده کن.</li>
                    </ol>

                    <div class="mini-note mt-3">
                        بهتر است نام‌گذاری و توضیحات حواله‌ها همیشه یکدست باشد تا بعداً در گزارش‌گیری و جستجو سریع‌تر به نتیجه برسی.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="outputs-box h-100">
                <div>
                    <div class="outputs-title">خروجی‌های انبار</div>
                    <p class="outputs-sub">
                        مشاهده لیست خروجی‌ها، بررسی تاریخچه حواله‌ها و آماده‌سازی برای تحلیل و گزارش‌های بعدی.
                    </p>
                </div>

                <a class="btn btn-light outputs-btn" href="{{ route('warehouse.outputs') }}">
                    ورود به خروجی‌های انبار
                </a>
            </div>
        </div>
    </div>
</div>
@endsection