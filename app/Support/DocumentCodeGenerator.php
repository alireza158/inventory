<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class DocumentCodeGenerator
{
    public const DEFAULT_WIDTH = 5;

    /**
     * Generates the next sequential numeric document code with leading zeros.
     *
     * Only existing codes that already match the requested width are considered,
     * so legacy 4-digit invoice/preinvoice numbers remain unchanged and do not
     * affect the new 5-digit sequence that starts at 00001.
     */
    public static function generateSequentialCode(string $modelClass, string $column = 'uuid', int $width = self::DEFAULT_WIDTH): string
    {
        if (!is_subclass_of($modelClass, Model::class)) {
            throw new RuntimeException("{$modelClass} must be an Eloquent model.");
        }

        if ($width < 1) {
            throw new RuntimeException('Document code width must be at least 1.');
        }

        $maxValue = $modelClass::query()
            ->pluck($column)
            ->filter(fn ($code) => is_string($code) && preg_match('/^\d{' . $width . '}$/', $code) === 1)
            ->map(fn ($code) => (int) $code)
            ->max() ?? 0;

        $limit = (10 ** $width) - 1;

        for ($next = $maxValue + 1; $next <= $limit; $next++) {
            $code = str_pad((string) $next, $width, '0', STR_PAD_LEFT);

            if (!$modelClass::query()->where($column, $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException("Unable to generate a unique {$width}-digit code.");
    }

    public static function generateUnique5DigitCode(string $modelClass, string $column = 'uuid'): string
    {
        return self::generateSequentialCode($modelClass, $column, self::DEFAULT_WIDTH);
    }

    public static function generateUnique4DigitCode(string $modelClass, string $column = 'uuid'): string
    {
        return self::generateUnique5DigitCode($modelClass, $column);
    }
}
