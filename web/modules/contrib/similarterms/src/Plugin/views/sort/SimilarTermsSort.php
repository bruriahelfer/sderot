<?php

namespace Drupal\similarterms\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Handler which sort by the similarity.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("similar_terms_sort")
 */
class SimilarTermsSort extends SortPluginBase {

  /**
   * Define default sorting order.
   *
   * @return string[]
   *   The options array.
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['order'] = ['default' => 'DESC'];
    return $options;
  }

  /**
   * Add orderBy.
   */
  public function query(): void {
    $this->ensureMyTable();
    $this->query->addOrderBy($this->tableAlias, 'nid', $this->options['order'], NULL, ['function' => 'count']);
  }

}
