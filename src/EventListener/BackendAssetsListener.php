<?php

namespace HeimrichHannot\TagsInput\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Routing\ScopeMatcher;
use HeimrichHannot\TagsInput\Widget\TagsInput;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsHook('initializeSystem')]
class BackendAssetsListener
{
    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        private readonly RequestStack $requestStack
    ) {
    }

    public function __invoke(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null || !$this->scopeMatcher->isBackendRequest($request)) {
            return;
        }

        // Turbo frame navigation does not load assets from the new page's head.
        TagsInput::loadAssets();
    }
}
