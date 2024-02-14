<?php

namespace HeimrichHannot\TagsInput\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelRequestListener implements EventSubscriberInterface
{
    protected ScopeMatcher $scopeMatcher;

    public function __construct(ScopeMatcher $scopeMatcher)
    {
        $this->scopeMatcher = $scopeMatcher;
    }
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest'
        ];
    }

    public function onKernelRequest(RequestEvent $e): void
    {
        $request = $e->getRequest();

        if ($this->scopeMatcher->isBackendRequest($request))
        {
            $GLOBALS['TL_JAVASCRIPT']['tagsinput-loaded'] = 'bundles/heimrichhannotcontaotagsinput/tagsinput.js|static';

            // JS: ['tagsinput']    = 'assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js|static';
            // JS: ['sortable']     = 'assets/vendor/Sortable/Sortable.min.js|static';
            // JS: ['typeahead']    = 'assets/vendor/corejs-typeahead/dist/typeahead.bundle.min.js|static';
            // JS: ['tagsinput-be'] = 'assets/js/jquery.tagsinput.min.js|static';

            $GLOBALS['TL_JAVASCRIPT']['tagsinput-be'] = 'bundles/heimrichhannotcontaotagsinput/assets/contao-tagsinput-be.js|static';

            // CSS: ['tagsinput'] = 'assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.css';
            // CSS: ['tagsinput-be'] = 'assets/css/bootstrap-tagsinput-be.css';
            // CSS: ['typeahead-be'] = 'assets/css/typeahead-be.css';

            $GLOBALS['TL_CSS']['tagsinput-be'] = 'bundles/heimrichhannotcontaotagsinput/assets/contao-tagsinput-be-theme.css';

            if (version_compare(VERSION, '5.0', '<')) {
                $GLOBALS['TL_CSS']['tagsinput-be-contao4'] = 'bundles/heimrichhannotcontaotagsinput/assets/contao-tagsinput-be-contao4-theme.css';
            }
        }
    }
}