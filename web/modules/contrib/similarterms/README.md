# Similar By Terms

This Drupal module attempts to provide context for content items by displaying a
view block with links to other similar content. Similarity is based on the
taxonomy terms assigned to content. Views are available based on similarity
within each of the defined vocabularies for a site as well as similarity within
all vocabularies.

For a full description of the module, visit the
[project page](https://drupal.org/project/similarterms)

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://drupal.org/project/issues/similarterms)

## Requirements

This module requires no modules outside of Drupal core.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

Configuration is accomplished per view:

1. Navigate to Administration » Structure » Views and create a view.
1. Add a contextual filter "Similar by terms: Nid".
1. Add a "Similar by terms: Similarity" sort criteria.
1. (Optional) Add the "Similar by terms: Similarity" field to output. The
similarity field can be configured to output the count of matching
terms or a percentage.
1. Save the View and place the block on a specific content type.

## Maintainers

- Robert Middleswarth - [rmiddle](https://www.drupal.org/u/rmiddle)
- Mustakimul Islam - [takim](https://www.drupal.org/u/takim)
- Shelane French - [shelane](https://www.drupal.org/u/shelane)
