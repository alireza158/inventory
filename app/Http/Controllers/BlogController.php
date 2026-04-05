<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $posts = collect($this->posts())
            ->sortByDesc('published_at')
            ->values();

        return view('blog.index', [
            'posts' => $posts,
            'pageTitle' => 'وبلاگ آریا جانبی | مقالات انبارداری، لجستیک و فروش',
            'pageDescription' => 'جدیدترین مقالات آموزشی درباره انبارداری حرفه‌ای، مدیریت حواله، کاهش خطا و بهینه‌سازی فرآیند فروش و ارسال.',
            'canonicalUrl' => route('blog.index'),
        ]);
    }

    public function show(string $slug)
    {
        $post = collect($this->posts())->firstWhere('slug', $slug);
        abort_unless($post, 404);

        $relatedPosts = collect($this->posts())
            ->where('slug', '!=', $slug)
            ->take(3)
            ->values();

        return view('blog.show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
            'pageTitle' => $post['title'] . ' | وبلاگ آریا جانبی',
            'pageDescription' => $post['excerpt'],
            'canonicalUrl' => route('blog.show', $post['slug']),
        ]);
    }

    private function posts(): array
    {
        return [
            [
                'slug' => 'smart-inventory-control-guide',
                'title' => 'راهنمای کنترل موجودی هوشمند در سال ۱۴۰۵',
                'excerpt' => 'چطور با گزارش‌گیری دقیق، نقطه سفارش و استانداردسازی انبار، از خواب سرمایه و کمبود کالا جلوگیری کنیم؟',
                'author' => 'تیم محتوا آریا جانبی',
                'category' => 'انبارداری',
                'read_time' => '۷ دقیقه',
                'published_at' => '2026-03-18',
                'hero_image' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1200&q=80',
                'content' => [
                    'کنترل موجودی فقط شمردن کالا نیست؛ تصمیم‌گیری لحظه‌ای برای خرید، تامین و فروش است.',
                    'برای شروع، ABC تحلیل را فعال کنید تا کالاهای پرفروش را از کالاهای کم‌گردش جدا ببینید.',
                    'سپس برای هر دسته، نقطه سفارش و موجودی اطمینان تعریف کنید تا تامین شما فعالانه شود نه واکنشی.',
                ],
            ],
            [
                'slug' => 'reduce-shipping-errors-with-vouchers',
                'title' => 'کاهش خطای ارسال با استانداردسازی حواله فروش',
                'excerpt' => 'با یک فلو ساده برای جمع‌آوری، کنترل و ارسال، درصد خطای ارسال سفارش را به حداقل برسانید.',
                'author' => 'تحریریه عملیات',
                'category' => 'لجستیک',
                'read_time' => '۵ دقیقه',
                'published_at' => '2026-02-27',
                'hero_image' => 'https://images.unsplash.com/photo-1553413077-190dd305871c?auto=format&fit=crop&w=1200&q=80',
                'content' => [
                    'بخش زیادی از خطاهای ارسال از ابهام حواله یا چندمنبعی بودن داده‌ها ایجاد می‌شود.',
                    'حواله فروش باید یک منبع حقیقت باشد: کد کالا، تنوع، تعداد، مقصد و وضعیت کنترل.',
                    'استفاده از ایستگاه کنترل نهایی قبل از خروج، خطای انسانی را به‌صورت محسوسی کاهش می‌دهد.',
                ],
            ],
            [
                'slug' => 'cashflow-insights-from-account-statement',
                'title' => 'تحلیل جریان نقدی مشتری از روی گردش حساب',
                'excerpt' => 'چگونه از گزارش گردش حساب برای شناسایی الگوهای پرداخت، ریسک اعتباری و تصمیم‌های فروش استفاده کنیم؟',
                'author' => 'تیم مالی',
                'category' => 'مالی',
                'read_time' => '۶ دقیقه',
                'published_at' => '2026-01-30',
                'hero_image' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=1200&q=80',
                'content' => [
                    'گردش حساب فقط یک جدول بدهکار/بستانکار نیست؛ تصویر رفتار مالی مشتری در زمان است.',
                    'با تفکیک پرداخت‌های چکی و نقدی، می‌توان الگوهای وصول و زمان‌بندی نقدینگی را دقیق‌تر دید.',
                    'از این داده‌ها برای تنظیم سقف اعتبار و پیشنهاد شرایط فروش متناسب استفاده کنید.',
                ],
            ],
        ];
    }
}
