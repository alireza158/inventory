@include('product-exports.pdf-start', [
    'meta' => $meta,
    'styles' => $styles,
    'skipImages' => $skipImages ?? false,
])
@include('product-exports.partials.pdf-rows', [
    'rows' => collect($rows)->values()->map(fn ($row, $index) => [
        'row' => $row,
        'number' => $index + 1,
    ])->all(),
    'skipImages' => $skipImages ?? false,
])
@include('product-exports.pdf-end')
