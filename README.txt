Adds support for displaying related local actions in a dropdown widget.

Example config in <module_name>.links.action.yml:
example_content.add:
  # The route name is required but it doesn't matter which route is used here, it will not be used for the actual links.
  route_name: view.example_content.page_1
  # Using the LocalActionWithDestination class adds a destination query string that redirects to the current page.
  class: \Drupal\Core\Menu\LocalActionWithDestination
  title: 'Add example content'
  appears_on:
    - view.example_content.page_1
  # Here are all the module-specific options that are further explained in the enum classes.
  options:
    widget: details
    links: entity_add
    entity_type: example_content
