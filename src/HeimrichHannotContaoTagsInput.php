<?php

/**
 * Copyright (c) 2024 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TagsInput;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeimrichHannotContaoTagsInput extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}