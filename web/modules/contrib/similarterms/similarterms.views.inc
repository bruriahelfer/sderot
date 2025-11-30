<?php

/**
 * @file
 * Provide a custom views field data that isn't tied to any other module.
 */

/**
 * Implements hook_views_data_alter().
 *
 * {@inheritdoc}
 */
function similarterms_views_data_alter(&$data): void {

  $data['node']['similarterms'] = [
    'group' => t('Similar by terms'),
    'title' => t('Similarity'),
    'help' => t('Percentage/count of terms which node has in common with node given as argument.'),
    'field' => [
      'id' => 'similar_terms_field',
    ],
    'sort' => [
      'id' => 'similar_terms_sort',
    ],
  ];

  $data['node']['similar_nid'] = [
    'title' => t('Nid'),
    'group' => t('Similar by terms'),
    'help' => t('ID of content item(s).'),
    'argument' => [
      'id' => 'similar_terms_arg',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'nid',
    ],
  ];
}
