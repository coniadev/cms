<?php

declare(strict_types=1);

namespace Conia\Field;

use Conia\Field;


class Markdown extends Field
{
    public function __toString(): string
    {
        return '';
    }
}