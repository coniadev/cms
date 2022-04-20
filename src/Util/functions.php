<?php

declare(strict_types=1);

if (!function_exists('env')) {
    function env(string $key, bool|string|null $default = null): mixed
    {
        if (func_num_args() > 1) {
            $value = $_ENV[$key] ?? null;

            if ($value === null) {
                return $default;
            }
        } else {
            $value = $_ENV[$key];
        }

        return match ($value) {
            'true' => true,
            'false' => false,
            'null' => null,
            'empty' => '',
            default => $value,
        };
    }
}