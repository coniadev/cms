<?php

declare(strict_types=1);

namespace Conia\Core\Value;

use Conia\Core\Assets\ResizeMode;
use Conia\Core\Assets\Size;
use Conia\Core\Exception\RuntimeException;
use Conia\Core\Exception\ValueError;
use Conia\Core\Field;
use Conia\Core\Node;
use Conia\Core\Util\Html as HtmlUtil;
use Generator;

class Grid extends Value
{
    protected readonly ?Generator $localizedData;

    public function __construct(Node $node, Field\Grid $field, ValueContext $context)
    {
        parent::__construct($node, $field, $context);

        $this->localizedData = match ($this->data['i18n'] ?? null) {
            'separate' => $this->getSeparate($this->data),
            'mixed' => $this->getMixed($this->data),
            null => null,
            default => throw new ValueError('Unknown i18n setting of Grid field'),
        };
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function json(): array
    {
        return $this->unwrap();
    }

    public function unwrap(): array
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
                    return (new Field\Image(
                        $this->context->fieldName,
                        $this->node,
                        new ValueContext($this->context->fieldName, $value->data)
                    ))->value();
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
                    return HtmlUtil::excerpt($value->data['value'], $words, $allowedTags);
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

    public function isset(): bool
    {
        if (is_null($this->localizedData)) {
            return false;
        }

        return match ($this->data['i18n'] ?? null) {
            'separate' => count($this->data[$this->defaultLocale->id]) > 0,
            // TODO: correct?
            'mixed' => count($this->data['value']) > 0,
            default => throw new ValueError('Unknown i18n setting of Grid field'),
        };
    }

    protected function renderValue(string $prefix, GridItem $value, array $args): string
    {
        $colspan = $prefix . '-colspan-' . $value->data['colspan'];
        $rowspan = $prefix . '-rowspan-' . $value->data['rowspan'];

        $out = '<div class="' . $prefix . '-' . $value->type . " {$colspan} {$rowspan}" . '">';
        $out .= match ($value->type) {
            'html' => $value->data['value'],
            'text' => $value->data['value'],
            'h1' => '<h1>' . $value->data['value'] . '</h1>',
            'h2' => '<h2>' . $value->data['value'] . '</h2>',
            'h3' => '<h3>' . $value->data['value'] . '</h3>',
            'h4' => '<h4>' . $value->data['value'] . '</h4>',
            'h5' => '<h5>' . $value->data['value'] . '</h5>',
            'h6' => '<h6>' . $value->data['value'] . '</h6>',
            'image' => $this->renderImage($value->data, $args),
            'youtube' => $this->getValueObject(Field\Youtube::class, $value)->__toString(),
        };
        $out .= '</div>';

        return $out;
    }

    protected function getValueObject(string $class, GridItem $item): Value
    {
        return (new $class(
            $this->context->fieldName,
            $this->node,
            new ValueContext($this->context->fieldName, $item->data)
        ))->value();
    }

    protected function renderImage(array $data, array $args): string
    {
        $file = $data['files'][0]['file'];
        $title = $data['files'][0]['title'] ?? '';
        $maxWidth = $args['maxImageWidth'] ?? 1280;
        $path = $this->assetsPath() . $file;
        $image = $this->getAssets()->image($path);
        $resized = $image->resize(
            new Size((int)($maxWidth / $this->columns()) * (int)($data['colspan'] ?? 12)),
            ResizeMode::Width,
            enlarge: false,
            quality: null,
        );
        $url = $resized->url(true);

        return "<img src=\"{$url}\" alt=\"{$title}\" data-path-original=\"{$path}\">";
    }

    // TODO: obviously
    protected function getMixed(array $data): array
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
