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
        '#items' => array_filter(
          array_map(
            function (array $option) use ($dropdown) {
              $url = Url::fromRoute($option['route_name'], $option['route_parameters'] ?? [], $dropdown['localized_options'] ?? []);

              return [
                '#type' => 'link',
                '#title' => $option['title'],
                '#fallback_title' => $option['fallback_title'],
                '#url' => $url,
                '#access' => $option['access'],
              ];
            },
            $dropdown['options']
          ),
          fn (array $item) => $item['#access']->isAllowed()
        )
      ],
    ];

    // Only display a details element if there is more than one item in the list.
    $itemCount = count($variables['dropdown']['content']['#items']);

    if($itemCount > 1) {
      $variables['add_wrapper'] = empty($variables['element']['#skip_wrapper']);
    } elseif($itemCount === 1) {
      $firstItem = reset($variables['dropdown']['content']['#items']);
      $variables['dropdown'] = [
        '#theme' => 'menu_local_action',
        '#link' => [
          'title' => $firstItem['#fallback_title'],
          'url' => $firstItem['#url'],
        ],
      ];
    } else {
      $variables['dropdown'] = [];
    }
  }
}
