<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class DocumentCodeGenerator
{
    public static function generateUnique4DigitCode(string $modelClass, string $column = 'uuid'): string
    {
        if (!is_subclass_of($modelClass, Model::class)) {
            throw new RuntimeException("{$modelClass} must be an Eloquent model.");
        }

        for ($i = 0; $i < 30; $i++) {
            $code = (string) random_int(1000, 9999);
            $exists = $modelClass::query()->where($column, $code)->exists();

            if (!$exists) {
                return $code;
            }
        }

        throw new RuntimeException('Unable to generate a unique 4-digit code.');
    }
}
