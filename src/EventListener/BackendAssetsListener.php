<?php

namespace HeimrichHannot\TagsInput\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Routing\ScopeMatcher;
use HeimrichHannot\TagsInput\Widget\TagsInput;

#[AsHook('initializeSystem')]
class BackendAssetsListener
{
    public function __construct(
        private readonly ScopeMatcher $scopeMatcher
    ) {
    }

    public function __invoke(): void
    {
        if (!$this->scopeMatcher->isBackendRequest()) {
            return;
        }

        // Turbo frame navigation does not load assets from the new page's head.
        TagsInput::loadAssets();
    }
}
