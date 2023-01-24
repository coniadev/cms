<?php

declare(strict_types=1);

namespace Conia\Core\Field;

use Conia\Core\Type;
use Conia\Core\Value\Files;
use Conia\Core\Value\ValueContext;

class File extends Field
{
    protected bool $single = false;

    public function value(Type $page, ValueContext $context): Files
    {
        return new Files($page, $context);
    }

    public function single(bool $single = true): static
    {
        $this->single = $single;

        return $this;
    }
}
