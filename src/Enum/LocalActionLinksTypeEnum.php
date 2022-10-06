<?php

namespace Drupal\actionlink_dropdown\Enum;

class LocalActionLinksTypeEnum {
  /**
   * Displays a list of custom links.
   *
   * Supported options:
   *
   * - array custom_links -- Container for the custom links. Supported suboptions:
   *  - string title
   *  - string route_name
   *  - array route_parameters (optional)
   * - string fallback_title_prefix -- The fallback title prefix, which is used if there is only one option.
   *
   * Example config options:
   *
   * custom_links:
   *   -
   *     title: 'Bundle 1'
   *     route_name: entity.example_content.add_form
   *     route_parameters:
   *       example_content_type: 'bundle_1'
   *   -
   *     title: 'Bundle 2'
   *     route_name: entity.example_content.add_form
   *     route_parameters:
   *       example_content_type: 'bundle_2'
   * fallback_title_prefix: 'Add'
   *
   * @see \Drupal\actionlink_dropdown\ValueObject\CustomLinksConfig
   */
  const CUSTOM = 'custom';

  /**
   * Displays a list of add links for an entity type.
   *
   * Supported options:
   *
   * - string entity_type -- The entity type id
   * - string fallback_title_prefix (optional) -- The fallback title prefix, which is used if there is only one option.
   *
   * Example config options:
   *
   * entity_type: example_content
   * fallback_title_prefix: 'Add new'
   *
   * @see \Drupal\actionlink_dropdown\ValueObject\EntityAddConfig
   */
  const ENTITY_ADD = 'entity_add';

}
