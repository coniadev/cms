<?php

declare(strict_types=1);

namespace Conia\Value;

use Conia\Chuck\Error\NoSuchProperty;
use Conia\Locale;
use Conia\Request;
use ReflectionClass;

abstract class Value
{
    public readonly string $fieldType;
    public readonly string $valueType;
    protected readonly Locale $locale;

    public function __construct(
        protected readonly Request $request,
        protected readonly array $data,
    ) {
        $this->locale = $request->locale();
        $this->fieldType = $data['type'];
        $this->valueType = (new ReflectionClass($this))->getShortName();
    }

    public function __get(string $name): mixed
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        throw new NoSuchProperty("The field doesn't have the property '{$name}'");
    }

    abstract public function __toString(): string;

    abstract public function json(): mixed;
}
