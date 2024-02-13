<?php

namespace HeimrichHannot\TagsInput;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeimrichHannotContaoTagsInput extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}