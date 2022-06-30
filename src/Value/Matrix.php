<?php

declare(strict_types=1);

namespace Conia\Value;

use Conia\Locale;


class Matrix extends Value
{
    protected readonly string $type;
    protected readonly array $localizedData;

    public function __construct(array $data, Locale $locale)
    {
        parent::__construct($data, $locale);

        $this->localizedData = match ($data['i18n']) {
            'separate' => $this->getSeparate($data),
            'mixed' => $this->getMixed($data),
        };
    }

    protected function getMixed(array $data): array
    {
        return $data;
    }

    protected function getSeparate(array $data): array
    {
        $locale = $this->locale;

        while ($locale) {
            $value = $data[$this->locale->id] ?? null;

            if ($value) return $value;

            $locale = $this->locale->fallback();
        }

        return [];
    }

    public function __toString(): string
    {
        return 'Matrix Field';
    }

    public function json(): mixed
    {
        return [
            'columns' => $this->data['columns'],
            'data' => $this->localizedData,
        ];
    }
}
