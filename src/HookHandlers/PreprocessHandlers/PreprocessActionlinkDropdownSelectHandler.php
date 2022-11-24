<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\HookHandlers\PreprocessHandlers;

use Drupal\Core\Url;

/**
 * Hook handler for hook_preprocess_actionlink_dropdown_select().
 */
class PreprocessActionlinkDropdownSelectHandler {
    public function preprocess(array &$variables): void {
        $dropdown = $variables['element']['#dropdown'];
        $variables['dropdown'] = [
            '#type' => 'select',
            '#options' => ['' => $dropdown['title']],
            '#attributes' => [
                'class' => [
                    'button',
                    'button-action',
                    'button--primary',
                    'button--small',
                ],
                'onchange' => 'if(value) window.location.href = value; value = \'\';',
            ],
            '#wrapper_attributes' => [
                'class' => ['actionlink-dropdown-select-container'],
            ],
        ];
        foreach ($dropdown['options'] as $option) {
            /** @var \Drupal\Core\Access\AccessResultInterface $accessResult */
            $accessResult = $option['access'];

            // Select lists don't support passing access information for individual
            // options, so we have to filter them manually.
            if (!$accessResult->isAllowed()) {
                continue;
            }

            $url = Url::fromRoute($option['route_name'], $option['route_parameters'] ?? [], $dropdown['localized_options'] ?? []);

            $variables['dropdown']['#options'][$url->toString()] = $option['title'];
        }

        $variables['add_wrapper'] = empty($variables['element']['#skip_wrapper']);
    }
}
