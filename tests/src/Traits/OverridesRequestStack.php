<?php

declare(strict_types=1);

namespace Drupal\Tests\actionlink_dropdown\Kernel\Traits;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Http\RequestStack;

trait OverridesRequestStack {
    protected function createRequestStack(): RequestStack {
        /** @var RequestStack $requestStack */
        $requestStack = \Drupal::service('request_stack');
        while ($requestStack->getCurrentRequest()) {
            $requestStack->pop();
        }

        $request = Request::create('/');
        $requestStack->push($request);

        return $requestStack;
    }
}
