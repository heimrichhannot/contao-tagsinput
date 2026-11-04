<?php

declare(strict_types=1);

use Contao\CoreBundle\Routing\ScopeMatcher;
use HeimrichHannot\TagsInput\EventListener\BackendAssetsListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

// An optional autoloader allows the same test to run against multiple Contao versions.
require $argv[1] ?? dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/Widget/TagsInput.php';
require dirname(__DIR__) . '/src/EventListener/BackendAssetsListener.php';

function scopeMatcher(string $scope): RequestMatcherInterface
{
    return new class($scope) implements RequestMatcherInterface {
        public function __construct(
            private readonly string $scope
        ) {
        }

        public function matches(Request $request): bool
        {
            return $this->scope === $request->attributes->get('_scope');
        }
    };
}

$stack = new RequestStack();
$arguments = [scopeMatcher('backend'), scopeMatcher('frontend')];

// Contao 5.7 added RequestStack to the matcher's constructor.
if ((new ReflectionMethod(ScopeMatcher::class, '__construct'))->getNumberOfParameters() === 3) {
    $arguments[] = $stack;
}

$listener = new BackendAssetsListener(new ScopeMatcher(...$arguments), $stack);
$failures = 0;

foreach (['backend', 'frontend', null, 'console', 'backend subrequest', 'frontend subrequest'] as $scope) {
    $GLOBALS['TL_JAVASCRIPT'] = [
        'existing' => 'existing.js',
    ];
    $GLOBALS['TL_CSS'] = [
        'existing' => 'existing.css',
    ];
    $expectedJs = $GLOBALS['TL_JAVASCRIPT'];
    $expectedCss = $GLOBALS['TL_CSS'];
    $requests = 0;

    if (str_contains((string) $scope, 'subrequest')) {
        $stack->push(new Request([], [], [
            '_scope' => $scope === 'backend subrequest' ? 'frontend' : 'backend',
        ]));
        ++$requests;
    }

    if ($scope !== 'console') {
        $stack->push(new Request([], [], [
            '_scope' => str_replace(' subrequest', '', (string) $scope),
        ]));
        ++$requests;
    }

    if ($scope === 'backend' || $scope === 'backend subrequest') {
        $expectedJs['tagsinput-be'] = 'bundles/heimrichhannotcontaotagsinput/assets/contao-tagsinput-be.js';
        $expectedCss['tagsinput-be-theme'] = 'bundles/heimrichhannotcontaotagsinput/assets/contao-tagsinput-be-theme.css';

        if (defined('VERSION') && version_compare(VERSION, '5.0', '<')) {
            $expectedCss['tagsinput-be-contao4-theme'] = 'bundles/heimrichhannotcontaotagsinput/assets/contao-tagsinput-be-contao4-theme.css';
        }
    }

    try {
        $listener();
        $listener(); // Repeated initialization must preserve existing registrations.

        if ($expectedJs !== $GLOBALS['TL_JAVASCRIPT'] || $expectedCss !== $GLOBALS['TL_CSS']) {
            throw new RuntimeException('Unexpected asset registrations.');
        }

        echo 'PASS: ', $scope ?? 'unscoped', PHP_EOL;
    } catch (Throwable $exception) {
        ++$failures;
        fwrite(STDERR, sprintf("FAIL: %s: %s\n", $scope ?? 'unscoped', $exception->getMessage()));
    } finally {
        while ($requests-- > 0) {
            $stack->pop();
        }
    }
}

exit($failures === 0 ? 0 : 1);
