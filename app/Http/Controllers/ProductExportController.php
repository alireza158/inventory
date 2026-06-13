<?php

namespace App\Http\Controllers;

use App\Exports\ProductRowsExport;
use App\Models\Category;
use App\Models\Warehouse;
use App\Services\ProductExportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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

        $format = $filters['format'];
        $filename = 'product-report-' . now()->format('Ymd-His');

        return match ($format) {
            'xlsx' => Excel::download(new ProductRowsExport($rows), $filename . '.xlsx'),
            'csv' => Excel::download(new ProductRowsExport($rows), $filename . '.csv', \Maatwebsite\Excel\Excel::CSV),
            'pdf' => $this->pdfResponse($rows, $filters, $filename),
        };
    }

    private function validatedFilters(Request $request, bool $requireFormat): array
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'stock_status' => ['nullable', 'in:all,in_stock,out_of_stock,low_stock'],
            'search' => ['nullable', 'string', 'max:255'],
            'format' => [$requireFormat ? 'required' : 'nullable', 'in:xlsx,excel,pdf,csv'],
        ]) + ['stock_status' => 'all'];

        if (($filters['format'] ?? null) === 'excel') {
            $filters['format'] = 'xlsx';
        }

        return $filters;
    }

    private function pdfResponse(array $rows, array $filters, string $filename)
    {
        $html = view('product-exports.pdf', [
            'rows' => $rows,
            'meta' => $this->service->meta($filters),
        ])->render();

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}.pdf\"",
            ]);
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.html\"",
        ]);
    }
}
