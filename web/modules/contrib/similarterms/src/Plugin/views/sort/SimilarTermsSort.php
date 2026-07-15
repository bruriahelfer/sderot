<?php

namespace Drupal\similarterms\Plugin\views\sort;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\join\Standard;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handler which sort by the similarity.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("similar_terms_sort")
 */
class SimilarTermsSort extends SortPluginBase {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs the SimilarTermsSort object.
   *
   * @param string[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string[] $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Define default sorting order.
   *
   * @return string[]
   *   The options array.
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['order'] = ['default' => 'DESC'];
    $options['sort_method'] = ['default' => 'count'];
    return $options;
  }

  /**
   * Build options settings form.
   *
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);

    $form['sort_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Sort method'),
      '#description' => $this->t('Choose how to calculate similarity for sorting.'),
      '#options' => [
        'count' => $this->t('Count of matching terms'),
        'weight' => $this->t('Sum of term weights (matching terms only)'),
      ],
      '#default_value' => $this->options['sort_method'] ?? 'count',
    ];
  }

  /**
   * Add orderBy.
   */
  public function query(): void {
    $this->ensureMyTable();

    $id_key = $this->entityTypeManager->getDefinition($this->getEntityType())->getKey('id');
    $sort_method = $this->options['sort_method'] ?? 'count';

    if ($sort_method === 'weight') {
      // Check if the similarterms_taxonomy_index relationship exists.
      // It won't exist if the argument node has no terms.
      $has_taxonomy_index = FALSE;
      if (isset($this->query->relationships['similarterms_taxonomy_index'])) {
        $has_taxonomy_index = TRUE;
      }

      if ($has_taxonomy_index) {
        // Join to taxonomy_term_field_data to access the weight column.
        $configuration = [
          'type' => 'LEFT',
          'table' => 'taxonomy_term_field_data',
          'field' => 'tid',
          'left_table' => 'similarterms_taxonomy_index',
          'left_field' => 'tid',
          'operator' => '=',
        ];
        $join = new Standard($configuration, 'similarterms_term_data', 'taxonomy_term_field_data');
        $alias = $this->query->addRelationship('similarterms_term_data', $join, 'similarterms_taxonomy_index');

        // Sort by SUM of term weights.
        $this->query->addOrderBy($alias, 'weight', $this->options['order'], NULL, ['function' => 'sum']);
      }
      else {
        // Fallback to count-based sorting if no taxonomy relationship exists.
        $this->query->addOrderBy($this->tableAlias, $id_key, $this->options['order'], NULL, ['function' => 'count']);
      }
    }
    else {
      // Default behavior: sort by COUNT of matching terms.
      $this->query->addOrderBy($this->tableAlias, $id_key, $this->options['order'], NULL, ['function' => 'count']);
    }
  }

}
