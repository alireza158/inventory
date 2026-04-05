@extends('layouts.app')

@section('title', $pageTitle)
@section('meta')
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $post['hero_image'] }}">
    <meta name="twitter:card" content="summary_large_image">
@endsection

@section('content')
<style>
    .article-head{
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 18px 46px rgba(15,23,42,.12);
    }
    .article-head img{ width: 100%; height: 320px; object-fit: cover; }
</style>

<article class="mb-4" itemscope itemtype="https://schema.org/BlogPosting">
    <header class="article-head mb-4 bg-white">
        <img src="{{ $post['hero_image'] }}" alt="{{ $post['title'] }}" itemprop="image">
        <div class="p-4">
            <a href="{{ route('blog.index') }}" class="btn btn-sm btn-outline-secondary mb-3">بازگشت به وبلاگ</a>
            <h1 class="fw-bold" itemprop="headline">{{ $post['title'] }}</h1>
            <div class="text-muted small d-flex gap-3 flex-wrap">
                <span>✍️ {{ $post['author'] }}</span>
                <span>⏱ {{ $post['read_time'] }}</span>
                <time datetime="{{ $post['published_at'] }}" itemprop="datePublished">📅 {{ $post['published_at'] }}</time>
            </div>
        </div>
    </header>

    <section class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <p class="lead text-muted" itemprop="description">{{ $post['excerpt'] }}</p>
            @foreach($post['content'] as $paragraph)
                <p itemprop="articleBody">{{ $paragraph }}</p>
            @endforeach
        </div>
    </section>
</article>

<section aria-label="مقالات مرتبط" class="mt-4">
    <h2 class="h5 fw-bold mb-3">مطالب مرتبط</h2>
    <div class="row g-3">
        @foreach($relatedPosts as $related)
            <div class="col-12 col-md-4">
                <a href="{{ route('blog.show', $related['slug']) }}" class="card h-100 text-decoration-none border-0 shadow-sm">
                    <img src="{{ $related['hero_image'] }}" alt="{{ $related['title'] }}" style="height:140px;object-fit:cover;">
                    <div class="card-body">
                        <h3 class="h6 text-dark fw-bold">{{ $related['title'] }}</h3>
                        <p class="small text-muted mb-0">{{ $related['excerpt'] }}</p>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</section>

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $post['title'],
    'datePublished' => $post['published_at'],
    'author' => ['@type' => 'Person', 'name' => $post['author']],
    'description' => $post['excerpt'],
    'image' => [$post['hero_image']],
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonicalUrl],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) !!}
</script>
@endsection
