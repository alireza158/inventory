<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Warehouse;
use App\Services\ProductExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductExportController extends Controller
{
    public function __construct(private readonly ProductExportService $service)
    {
    }

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request, false);
        $categories = Category::query()->orderBy('name')->get();
        $warehouses = Warehouse::query()->orderBy('name')->get();
        $products = $this->service->filteredProducts($filters);
        $rows = $products->map(fn ($product) => $this->service->row($product));

        return view('product-exports.index', compact('categories', 'warehouses', 'filters', 'rows'));
    }

    public function filter(Request $request)
    {
        $filters = $this->validatedFilters($request, false);
        $rows = collect($this->service->rows($filters));

        return view('product-exports.partials.table', compact('rows'))->render();
    }

    public function export(Request $request)
    {
        $filters = $this->validatedFilters($request, true);
        $rows = $this->service->rows($filters);

        if (empty($rows)) {
            return back()->with('error', 'محصولی برای این دسته‌بندی یافت نشد.');
        }

        $filename = 'product-report-' . now()->format('Ymd-His');

        return $this->pdfResponse($rows, $filters, $filename);
    }

    private function validatedFilters(Request $request, bool $requireFormat): array
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'stock_status' => ['nullable', 'in:all,in_stock,out_of_stock,low_stock'],
            'search' => ['nullable', 'string', 'max:255'],
            'format' => [$requireFormat ? 'required' : 'nullable', 'in:pdf'],
        ]) + ['stock_status' => 'all', 'format' => 'pdf'];

        $filters['format'] = 'pdf';

        return $filters;
    }

    private function pdfResponse(array $rows, array $filters, string $filename)
    {
        if (! class_exists(\Dompdf\Dompdf::class)) {
            return back()->with('error', 'امکان ساخت PDF فعال نیست؛ لطفاً بعد از دریافت آخرین تغییرات، دستور composer install یا composer update dompdf/dompdf را روی سرور اجرا کنید.');
        }

        try {
            $html = view('product-exports.pdf', [
                'rows' => $rows,
                'meta' => $this->service->meta($filters),
            ])->render();

            $options = new \Dompdf\Options();
            $options->set('defaultFont', 'Vazirmatn');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('tempDir', storage_path('app/dompdf-temp'));
            $options->set('fontCache', storage_path('app/dompdf-font-cache'));
            $options->set('chroot', [public_path(), storage_path('app/public')]);

            foreach (['app/dompdf-temp', 'app/dompdf-font-cache'] as $directory) {
                if (! is_dir(storage_path($directory))) {
                    mkdir(storage_path($directory), 0775, true);
                }
            }

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}.pdf\"",
            ]);
        } catch (Throwable $exception) {
            Log::error('Product PDF export failed', [
                'message' => $exception->getMessage(),
                'filters' => $filters,
            ]);

            return back()->with('error', 'ساخت فایل PDF با خطا روبه‌رو شد. لطفاً دوباره تلاش کنید یا لاگ سرور را بررسی کنید.');
        }
    }
}
