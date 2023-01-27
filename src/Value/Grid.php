<?php

declare(strict_types=1);

namespace Conia\Core\Value;

use Conia\Core\Exception\RuntimeException;
use Conia\Core\Exception\ValueError;
use Conia\Core\Field\Grid as GridField;
use Conia\Core\Field\Html as HtmlField;
use Conia\Core\Field\Image as ImageField;
use Conia\Core\Type;
use Generator;

class Grid extends Value
{
    protected readonly Generator $localizedData;

    public function __construct(Type $page, GridField $field, ValueContext $context)
    {
        parent::__construct($page, $field, $context);

        $this->localizedData = match ($this->data['i18n'] ?? null) {
            'separate' => $this->getSeparate($this->data),
            'mixed' => $this->getMixed($this->data),
            default => throw new ValueError('Unknown i18n setting of Grid field'),
        };
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function json(): mixed
    {
        return [
            'columns' => $this->data['columns'] ?? null,
            'data' => $this->localizedData,
        ];
    }

    public function image(int $index = 1): ?Image
    {
        $i = 0;

        foreach ($this->localizedData as $value) {
            if ($value->type === 'image') {
                $i++;

                if ($i === $index) {
                    return (new ImageField("Grid {$this->context->fieldName} Image Field"))
                        ->single()
                        ->value(
                            $this->page,
                            new ValueContext($this->context->fieldName, $value->data)
                        );
                }
            }
        }

        return null;
    }

    public function hasImage(int $index = 1): bool
    {
        $i = 0;
        foreach ($this->localizedData as $value) {
            if ($value->type === 'image') {
                $i++;

                if ($i === $index) {
                    return true;
                }
            }
        }

        return false;
    }

    public function excerpt(
        int $words = 30,
        string $allowedTags = '<a><i><b><u><em><strong>',
        int $index = 1
    ): string {
        $i = 0;

        foreach ($this->localizedData as $value) {
            if ($value->type === 'html') {
                $i++;

                if ($i === $index) {
                    return (new HtmlField("Grid {$this->context->fieldName} Html Field"))
                        ->value(
                            $this->page,
                            new ValueContext($this->context->fieldName, $value->data)
                        )->excerpt($words, $allowedTags);
                }
            }
        }

        return '';
    }

    public function columns(): int
    {
        return (int)($this->data['columns'] ?? 12);
    }

    // Supported args:
    //
    // - prefix: All css classes are prefixed with this value. Default 'conia'
    // - tag: The tag of the container. Default 'div'
    // - maxImageWidth: The maximum width of images. Images will be resized according to colspan. Default: 1280
    // - class: An additional class added to the container
    public function render(mixed ...$args): string
    {
        $args['tag'] = $tag = $args['tag'] ?? 'div';
        $args['prefix'] = $prefix = $args['prefix'] ?? 'conia';
        $args['class'] = $class = ($args['class'] ?? '' ? ' ' . $args['class'] : '');

        $columns = $this->columns();

        $out = '<' . $tag . ' class="' . $prefix . '-grid ' . $prefix .
            '-grid-columns-' . $columns . $class . '">';

        foreach ($this->localizedData as $value) {
            $out .= $this->renderValue($prefix, $value, $args);
        }

        $out .= '</' . $tag . '>';

        return $out;
    }

    protected function renderValue(string $prefix, GridItem $value, array $args): string
    {
        $colspan = $prefix . '-colspan-' . $value->data['colspan'];
        $rowspan = $prefix . '-rowspan-' . $value->data['rowspan'];

        $out = '<div class="' . $prefix . '-' . $value->type . " {$colspan} {$rowspan}" . '">';
        $out .= match ($value->type) {
            'html' => $value->data['value'],
            'text' => $value->data['value'],
            'image' => $this->renderImage($value->data, $args),
        };
        $out .= '</div>';

        return $out;
    }

    protected function renderImage(array $data, array $args): string
    {
        $file = $data['files'][0]['file'];
        $title = $data['files'][0]['title'] ?? '';
        $maxWidth = $args['maxImageWidth'] ?? 1280;
        $path = $this->assetsPath() . $file;
        $image = $this->getAssets()->image($path);
        $resized = $image->resize((int)($maxWidth / $this->columns() * (int)($data['colspan'] ?? 12)));
        $url = $resized->url(true);

        return "<img src=\"{$url}\" alt=\"{$title}\">";
    }

    protected function getMixed(array $data): Generator
    {
        throw new RuntimeException('Not implemented');
    }

    protected function getSeparate(array $data): Generator
    {
        $locale = $this->locale;

        while ($locale) {
            $fields = $data[$this->locale->id] ?? null;

            if ($fields) {
                foreach ($fields as $field) {
                    yield new GridItem($field['type'], $field);
                }

                break;
            }

            $locale = $locale->fallback();
        }
    }
}