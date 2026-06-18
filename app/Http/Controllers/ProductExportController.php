<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Warehouse;
use App\Services\ProductExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use RuntimeException;
use Throwable;

class ProductExportController extends Controller
{
    public function __construct(
        private readonly ProductExportService $service
    ) {
    }

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);

        $categories = Category::query()
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::query()
            ->orderBy('name')
            ->get();

        $rows = collect($this->service->rows($filters));

        return view('product-exports.index', compact(
            'categories',
            'warehouses',
            'filters',
            'rows'
        ));
    }

    public function filter(Request $request): string
    {
        $filters = $this->validatedFilters($request);
        $rows = collect($this->service->rows($filters));

        return view('product-exports.partials.table', compact('rows'))
            ->render();
    }

    public function export(Request $request)
    {
        $filters = $this->validatedFilters($request, true);
        $rows = $this->service->rows($filters);

        if (empty($rows)) {
            return back()->with(
                'error',
                'محصولی مطابق فیلترهای انتخاب‌شده پیدا نشد.'
            );
        }

        try {
            $this->preparePdfEnvironment();

            $mpdf = $this->createMpdf();

            $meta = $this->service->meta($filters);

            /*
             * CSS و HTML جداگانه ارسال می‌شوند تا از خروجی سفید
             * در HTMLهای حجیم جلوگیری شود.
             */
            $mpdf->WriteHTML(
                $this->pdfStyles(),
                HTMLParserMode::HEADER_CSS
            );

            $mpdf->SetHTMLFooter(
                $this->pdfFooter($meta)
            );

            $html = view('product-exports.pdf', [
                'rows' => $rows,
                'meta' => $meta,
            ])->render();

            $mpdf->WriteHTML(
                $html,
                HTMLParserMode::HTML_BODY
            );

            $filename = 'product-report-'
                . now()->format('Ymd-His')
                . '.pdf';

            $pdfContent = $mpdf->Output(
                '',
                Destination::STRING_RETURN
            );

            if ($pdfContent === '') {
                throw new RuntimeException(
                    'محتوای PDF تولید نشد.'
                );
            }

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                    'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($pdfContent),
                'Cache-Control' =>
                    'private, no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ]);
        } catch (Throwable $exception) {
            Log::error('Product PDF export failed.', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'filters' => $filters,
                'trace' => $exception->getTraceAsString(),
            ]);

            return back()->with(
                'error',
                'خطا در ساخت PDF: ' . $exception->getMessage()
            );
        }
    }

    private function createMpdf(): Mpdf
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $defaultFonts = (new FontVariables())->getDefaults();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',

            /*
             * افقی انتخاب شده تا جدول فشرده یا خراب نشود.
             */
            'format' => 'A4-L',

            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 10,
            'margin_bottom' => 14,
            'margin_header' => 0,
            'margin_footer' => 6,

            'tempDir' => storage_path('app/mpdf-temp'),

            'fontDir' => array_merge(
                $defaultConfig['fontDir'],
                [storage_path('fonts')]
            ),

            'fontdata' => $defaultFonts['fontdata'] + [
                'vazirmatn' => [
                    'R' => 'Vazirmatn-Regular.ttf',
                    'B' => 'Vazirmatn-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],

            'default_font' => 'vazirmatn',
            'default_font_size' => 9,

            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useSubstitutions' => true,

            /*
             * از simpleTables، autosize و تنظیمات مشکل‌دار
             * استفاده نمی‌کنیم.
             */
            'use_kwt' => false,
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle('گزارش محصولات');
        $mpdf->SetAuthor(config('app.name', 'سامانه انبارداری'));
        $mpdf->SetCreator(config('app.name', 'سامانه انبارداری'));

        /*
         * موقتاً برای پیدا کردن خطاهای مخفی فعال باشد.
         */
        $mpdf->debug = true;
        $mpdf->showImageErrors = true;

        return $mpdf;
    }

    private function preparePdfEnvironment(): void
    {
        $directories = [
            storage_path('fonts'),
            storage_path('app/mpdf-temp'),
        ];

        foreach ($directories as $directory) {
            if (! File::isDirectory($directory)) {
                File::makeDirectory(
                    $directory,
                    0775,
                    true,
                    true
                );
            }
        }

        $fonts = [
            storage_path('fonts/Vazirmatn-Regular.ttf'),
            storage_path('fonts/Vazirmatn-Bold.ttf'),
        ];

        foreach ($fonts as $font) {
            if (! File::isFile($font)) {
                throw new RuntimeException(
                    'فایل فونت وجود ندارد: ' . $font
                );
            }

            if (! File::isReadable($font)) {
                throw new RuntimeException(
                    'فایل فونت قابل خواندن نیست: ' . $font
                );
            }

            if (File::size($font) < 10000) {
                throw new RuntimeException(
                    'فایل فونت معتبر نیست یا ناقص دانلود شده: ' . $font
                );
            }
        }
    }

    private function pdfFooter(array $meta): string
    {
        $storeName = e(
            $meta['store_name']
                ?? config('app.name', 'سامانه انبارداری')
        );

        return '
            <div style="
                direction: rtl;
                font-family: vazirmatn;
                font-size: 8pt;
                color: #64748b;
                border-top: 1px solid #cbd5e1;
                padding-top: 4px;
            ">
                <table width="100%" style="border-collapse: collapse;">
                    <tr>
                        <td style="text-align: right; border: 0;">
                            ' . $storeName . '
                        </td>

                        <td style="text-align: left; border: 0;">
                            صفحه {PAGENO} از {nbpg}
                        </td>
                    </tr>
                </table>
            </div>
        ';
    }

    private function pdfStyles(): string
    {
        return <<<'CSS'
        body {
            direction: rtl;
            text-align: right;
            font-family: vazirmatn;
            font-size: 9pt;
            color: #172033;
            line-height: 1.6;
        }

        table {
            font-family: vazirmatn;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            background-color: #1e293b;
        }

        .header td {
            padding: 12px;
            border: 0;
            color: #ffffff;
        }

        .title {
            font-size: 17pt;
            font-weight: bold;
            color: #ffffff;
        }

        .subtitle {
            margin-top: 3px;
            color: #cbd5e1;
            font-size: 8pt;
        }

        .date {
            width: 28%;
            text-align: left;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .meta-table td {
            padding: 6px;
            border: 1px solid #dbe3ec;
            background-color: #f8fafc;
        }

        .meta-label {
            color: #64748b;
            font-size: 7pt;
        }

        .meta-value {
            color: #1e293b;
            font-weight: bold;
        }

        .summary {
            padding: 6px 8px;
            margin-bottom: 8px;
            background-color: #eff6ff;
            border-right: 3px solid #2563eb;
            color: #1e40af;
        }

        .products {
            width: 100%;
            border-collapse: collapse;
        }

        .products th {
            padding: 6px 3px;
            border: 1px solid #334155;
            background-color: #334155;
            color: #ffffff;
            text-align: center;
            font-size: 7.5pt;
        }

        .products td {
            padding: 5px 3px;
            border: 1px solid #dbe3ec;
            text-align: center;
            vertical-align: middle;
        }

        .product-name {
            text-align: right;
            font-weight: bold;
        }

        .small {
            color: #64748b;
            font-size: 7pt;
        }

        .ltr {
            direction: ltr;
            text-align: center;
        }

        .image {
            width: 36px;
            height: 36px;
        }

        .status {
            font-size: 7pt;
            font-weight: bold;
            padding: 2px 5px;
        }

        .success {
            color: #067647;
            background-color: #dcfae6;
        }

        .warning {
            color: #9a6700;
            background-color: #fef0c7;
        }

        .danger {
            color: #b42318;
            background-color: #fee4e2;
        }

        .variant td {
            background-color: #f1f5f9;
            font-size: 7.5pt;
        }
        CSS;
    }

    private function validatedFilters(
        Request $request,
        bool $requireFormat = false
    ): array {
        $validated = $request->validate([
            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'warehouse_id' => [
                'nullable',
                'integer',
                'exists:warehouses,id',
            ],
            'stock_status' => [
                'nullable',
                'in:all,in_stock,out_of_stock,low_stock',
            ],
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'format' => [
                $requireFormat ? 'required' : 'nullable',
                'in:pdf',
            ],
        ]);

        return [
            'category_id' => $validated['category_id'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'stock_status' => $validated['stock_status'] ?? 'all',
            'search' => trim((string) ($validated['search'] ?? '')),
            'format' => 'pdf',
        ];
    }
}