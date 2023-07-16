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

    // Filter out options that are not allowed.
    $dropdown['options'] = array_filter($dropdown['options'], fn (array $option) => $option['access']->isAllowed());

    // Only display a select list if there is more than one option in the
    // select list.
    if (count($dropdown['options']) > 1) {
      foreach ($dropdown['options'] as $option) {
        $url = Url::fromRoute($option['route_name'], $option['route_parameters'] ?? [], $dropdown['localized_options'] ?? []);

        $variables['dropdown']['#options'][$url->toString()] = $option['title'];
      }

      $variables['add_wrapper'] = empty($variables['element']['#skip_wrapper']);
    }
    elseif (count($dropdown['options']) === 1) {
      $firstOption = reset($dropdown['options']);

      $variables['dropdown'] = [
        '#theme' => 'menu_local_action',
        '#link' => [
          'title' => $firstOption['fallback_title'],
          'url' => Url::fromRoute($firstOption['route_name'], $firstOption['route_parameters'] ?? [], $dropdown['localized_options'] ?? []),
        ],
      ];
    }
    else {
      $variables['dropdown'] = [];
    }
  }

}
