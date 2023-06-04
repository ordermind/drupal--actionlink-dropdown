<?php

declare(strict_types=1);

namespace Drupal\Tests\actionlink_dropdown\Kernel\Concerns;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

trait OverridesRequestStack {

  protected function createRequestStack(): RequestStack {
    $requestStack = \Drupal::service('request_stack');
    while ($requestStack->getCurrentRequest()) {
      $requestStack->pop();
    }

    $request = Request::create('/');
    $requestStack->push($request);

    return $requestStack;
  }
}
