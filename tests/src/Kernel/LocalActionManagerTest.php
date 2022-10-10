<?php

declare(strict_types=1);

namespace Drupal\Tests\actionlink_dropdown\Kernel;

use Drupal\Core\Menu\LocalActionManagerInterface;
use Drupal\KernelTests\KernelTestBase;

class LocalActionManagerTest extends KernelTestBase {
    protected LocalActionManagerInterface $localActionManager;

    protected function setUp(): void {
        $this->localActionManager = \Drupal::service('plugin.manager.menu.local_action');
    }

    public function testRegularElement(): void {
    }
}
