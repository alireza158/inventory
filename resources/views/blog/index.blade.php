@extends('layouts.app')

@section('title', $pageTitle)
@section('meta')
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta name="twitter:card" content="summary_large_image">
@endsection

@section('content')
<style>
    .blog-hero{
        border-radius: 24px;
        padding: 2rem;
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 55%, #0ea5e9 100%);
        color: #fff;
        box-shadow: 0 18px 48px rgba(30,64,175,.25);
    }
    .blog-card{
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease;
        box-shadow: 0 10px 26px rgba(15,23,42,.08);
        height: 100%;
    }
    .blog-card:hover{ transform: translateY(-6px); box-shadow: 0 18px 38px rgba(15,23,42,.14); }
    .blog-card .thumb{ height: 190px; object-fit: cover; }
    .chip{ border-radius: 999px; padding: .25rem .75rem; background: #eff6ff; color: #1d4ed8; font-size: .8rem; }
</style>

<header class="blog-hero mb-4">
    <p class="small mb-2">وبلاگ تخصصی آریا جانبی</p>
    <h1 class="fw-bold mb-3">مقالات خفن در انبارداری، فروش و لجستیک 🚀</h1>
    <p class="mb-0 opacity-75">اینجا هر هفته راهکارهای عملی می‌ذاریم تا سیستم انبار و فروش شما سریع‌تر، کم‌خطاتر و سودده‌تر بشه.</p>
</header>

<section aria-label="لیست مقالات وبلاگ">
    <div class="row g-4">
        @foreach($posts as $post)
            <div class="col-12 col-md-6 col-xl-4">
                <article class="card blog-card">
                    <img src="{{ $post['hero_image'] }}" class="thumb w-100" alt="{{ $post['title'] }}">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="chip">{{ $post['category'] }}</span>
                            <small class="text-muted">{{ $post['read_time'] }}</small>
                        </div>
                        <h2 class="h5 fw-bold">{{ $post['title'] }}</h2>
                        <p class="text-muted small flex-grow-1">{{ $post['excerpt'] }}</p>
                        <a href="{{ route('blog.show', $post['slug']) }}" class="btn btn-primary w-100">مطالعه مقاله</a>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
</section>

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Blog',
    'name' => 'وبلاگ آریا جانبی',
    'url' => $canonicalUrl,
    'description' => $pageDescription,
    'blogPost' => $posts->map(fn($post) => [
        '@type' => 'BlogPosting',
        'headline' => $post['title'],
        'datePublished' => $post['published_at'],
        'url' => route('blog.show', $post['slug']),
        'author' => ['@type' => 'Person', 'name' => $post['author']],
    ])->values(),
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) !!}
</script>
@endsection
