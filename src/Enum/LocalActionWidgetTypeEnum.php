<?php

namespace Drupal\actionlink_dropdown\Enum;

class LocalActionWidgetTypeEnum
{
  /**
   * Displays the local actions in a select list, suitable for mobile layouts.
   */
  const SELECT = 'select';

  /**
   * Displays the local actions in a details element, suitable for all layouts.
   */
  const DETAILS = 'details';

  /**
   * Displays the local actions in both a details element and a select list, for the purpose of responsive design.
   */
  const DETAILS_PLUS_SELECT = 'details_select';
}
