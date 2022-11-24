<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\HookHandlers\PreprocessHandlers;

use Drupal\Core\Url;

/**
 * Hook handler for hook_preprocess_actionlink_dropdown_details().
 */
class PreprocessActionlinkDropdownDetailsHandler {

  public function preprocess(array &$variables): void {
    $dropdown = $variables['element']['#dropdown'];
    $variables['dropdown'] = [
      '#type' => 'details',
      '#title' => $dropdown['title'],
      '#attributes' => [
        'class' => [
          'button',
          'button-action',
          'button--primary',
          'button--small',
        ],
      ],
      'content' => [
        '#theme' => 'item_list',
        '#type' => 'ul',
        '#items' => array_map(
                  function (array $option) use ($dropdown) {
                      $url = Url::fromRoute($option['route_name'], $option['route_parameters'] ?? [], $dropdown['localized_options'] ?? []);

                      return [
                        '#type' => 'link',
                        '#title' => $option['title'],
                        '#url' => $url,
                        '#access' => $option['access'],
                      ];
                  },
                  $dropdown['options']
        ),
      ],
    ];

    $variables['add_wrapper'] = empty($variables['element']['#skip_wrapper']);
  }

}
