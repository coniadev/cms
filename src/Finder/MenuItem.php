<?php

declare(strict_types=1);

namespace Conia\Core\Finder;

use Conia\Core\Context;
use Generator;
use Iterator

class MenuItem implements Iterator
{
    protected readonly array $data;
    protected array $children = [];

    public function __construct(
        protected readonly Context $context,
        protected readonly array $item
    ) {
        $this->data = json_decode($item['data'], true);
        error_log('gemacht');
    }

    public function rewind(): void
    {
        reset($this->children);
    }

    public function current(): MenuItem
    {
        return new MenuItem($this->context, current($this->children));
    }

    public function key(): string
    {
        return key($this->children);
    }

    public function next(): void
    {
        next($this->children);
    }

    public function valid(): bool
    {
        return key($this->children) !== null;
    }

    public function type(): string
    {
        return $this->item['type'];
    }

    public function title(): string
    {
        return $this->translated('title');
    }

    public function path(): string
    {
        return $this->translated('path');
    }

    public function children(): Generator
    {
        foreach ($this->children as $child) {
            yield new MenuItem($this->context, $child);
        }
    }

    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    protected function translated(string $key): string
    {
        $locale = $this->context->locale();

        while ($locale) {
            $value = $this->data[$key][$locale->id] ?? null;

            if ($value) {
                return $value;
            }

            $locale = $locale->fallback();
        }

        return '';
    }
}
