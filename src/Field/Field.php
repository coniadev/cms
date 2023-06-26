<?php

declare(strict_types=1);

namespace Conia\Core\Field;

use Conia\Core\Field\Attr\FulltextWeight;
use Conia\Core\Node;
use Conia\Core\Value\Value;
use Conia\Core\Value\ValueContext;

abstract class Field
{
    public readonly string $type;
    protected ?string $label = null;
    protected ?string $description = null;
    protected bool $translate = false;
    protected bool $required = false;
    protected ?int $width = null;
    protected ?int $rows = null;
    protected ?FulltextWeight $fulltextWeight = null;

    public function __construct(
        protected readonly string $name,
        protected readonly Node $node,
        protected readonly ValueContext $valueContext
    ) {
        $this->type = $this::class;
    }

    public function __toString(): string
    {
        return $this->value()->__toString();
    }

    abstract public function value(): Value;
    abstract public function structure(): array;

    public function isset(): bool
    {
        return $this->value()->isset();
    }

    public function validate(): bool
    {
        return true;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function translate(bool $translate = true): static
    {
        $this->translate = $translate;

        return $this;
    }

    public function isTranslatable(): bool
    {
        return $this->translate;
    }

    public function fulltext(FulltextWeight $fulltextWeight): static
    {
        $this->fulltextWeight = $fulltextWeight;

        return $this;
    }

    public function getFulltextWeight(): ?FulltextWeight
    {
        return $this->fulltextWeight;
    }

    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function rows(int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    public function getRows(): int
    {
        return $this->rows;
    }

    public function asArray(): array
    {
        return [
            'rows' => $this->rows,
            'width' => $this->width,
            'translate' => $this->translate,
            'required' => $this->required,
            'description' => $this->description,
            'label' => $this->label,
            'name' => $this->name,
            'type' => $this::class,
        ];
    }

    public function getFileStructure(string $type): array
    {
        return ['type' => $type, 'files' => []];
    }

    public function getSimpleStructure(string $type): array
    {
        return ['type' => $type, 'value' => null];
    }

    protected function getTranslatableStructure(string $type): array
    {
        $result = ['type' => $type];

        if ($this->translate) {
            $result['value'] = [];
            foreach ($this->node->config->locales() as $locale) {
                $result['value'][$locale->id] = null;
            }
        } else {
            $result['value'] = null;
        }

        return $result;
    }
}
