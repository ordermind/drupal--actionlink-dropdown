<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\HookHandlers\PreprocessHandlers;

/**
 * Hook handler for hook_preprocess_actionlink_dropdown_details_select().
 */
class PreprocessActionlinkDropdownDetailsSelectHandler {

  public function preprocess(array &$variables): void {
    $variables['dropdown'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'actionlink-dropdown-details-select-container',
        ],
      ],
      'details' => [
        '#theme' => 'actionlink_dropdown_details',
        '#dropdown' => $variables['element']['#dropdown'],
        '#skip_wrapper' => TRUE,
      ],
      'select' => [
        '#theme' => 'actionlink_dropdown_select',
        '#dropdown' => $variables['element']['#dropdown'],
        '#skip_wrapper' => TRUE,
      ],
    ];
  }

}
