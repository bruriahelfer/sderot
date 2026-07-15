<?php

namespace Drupal\similarterms\Plugin\views\field;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\join\Standard;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Shows the similarity of the node.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("similar_terms_field")
 */
class SimilarTermsField extends FieldPluginBase {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs the SimilarTermsField object.
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
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $id_key = $this->entityTypeManager->getDefinition($this->getEntityType())->getKey('id');

    // If weight sum is selected, we need to join to taxonomy_term_field_data
    // and sum the weights instead of counting terms.
    if ($this->options['count_type'] == 2) {
      // Check if similarterms_taxonomy_index table is in the query.
      $tables = $this->query->tables;
      $taxonomy_index_alias = NULL;

      // Find the similarterms_taxonomy_index table alias.
      foreach ($tables as $table_info) {
        if (isset($table_info['similarterms_taxonomy_index'])) {
          $taxonomy_index_alias = 'similarterms_taxonomy_index';
          break;
        }
      }

      if ($taxonomy_index_alias) {
        // Join to taxonomy_term_field_data to access weights.
        $configuration = [
          'type' => 'LEFT',
          'table' => 'taxonomy_term_field_data',
          'field' => 'tid',
          'left_table' => $taxonomy_index_alias,
          'left_field' => 'tid',
          'operator' => '=',
        ];
        $join = new Standard($configuration, 'similarterms_taxonomy_term_field_data', 'taxonomy_term_field_data');
        $this->query->addRelationship('similarterms_taxonomy_term_field_data', $join, $taxonomy_index_alias);

        $params = [
          'function' => 'sum',
        ];
        $this->field_alias = $this->query->addField('similarterms_taxonomy_term_field_data', 'weight', NULL, $params);
      }
      else {
        // Fallback to count if taxonomy_index relationship doesn't exist.
        $params = [
          'function' => 'count',
        ];
        $this->field_alias = $this->query->addField($this->tableAlias, $id_key, NULL, $params);
      }
    }
    else {
      // Default behavior for count and percentage.
      // Check if the similarterms_taxonomy_index relationship exists.
      // It won't exist if the argument node has no terms.
      if (isset($this->query->relationships['similarterms_taxonomy_index'])) {
        // Count taxonomy_index.tid (matching terms) not node.nid.
        // This ensures nodes with no matching terms show 0, not 1.
        $params = [
          'function' => 'count',
        ];
        $this->field_alias = $this->query->addField('similarterms_taxonomy_index', 'tid', NULL, $params);
      }
      else {
        // Fallback when no taxonomy relationship exists.
        $params = [
          'function' => 'count',
        ];
        $this->field_alias = $this->query->addField($this->tableAlias, $id_key, NULL, $params);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['count_type'] = ['default' => 1];
    $options['percent_suffix'] = ['default' => 1];
    $options['weight_suffix'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['count_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display type'),
      '#default_value' => $this->options['count_type'],
      '#options' => [
        0 => $this->t('Show count of common terms'),
        1 => $this->t('Show as percentage'),
        2 => $this->t('Show sum of term weights'),
      ],
    ];

    $form['percent_suffix'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Append % when showing percentage'),
      '#default_value' => !empty($this->options['percent_suffix']),
      '#states' => [
        'visible' => [
          ':input[name="options[count_type]"]' => ['value' => '1'],
        ],
      ],
    ];

    $form['weight_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix for weight display'),
      '#description' => $this->t('Optional text to append after the weight sum (e.g., " pts").'),
      '#default_value' => $this->options['weight_suffix'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="options[count_type]"]' => ['value' => '2'],
        ],
      ],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values): MarkupInterface|string {

    if ($this->options['count_type'] == 0) {
      // Show count of common terms.
      return $values->{$this->field_alias} ?? 0;
    }
    elseif ($this->options['count_type'] == 2) {
      // Show sum of term weights.
      $weight = $values->{$this->field_alias} ?? 0;
      $output = $weight;
      if (!empty($this->options['weight_suffix'])) {
        $output .= $this->options['weight_suffix'];
      }
      return $output;
    }
    elseif (isset($this->view->tids) && count($this->view->tids) > 0) {
      // Show as percentage.
      $count = $values->{$this->field_alias} ?? 0;
      $output = round($count / count($this->view->tids) * 100);
      if (!empty($this->options['percent_suffix'])) {
        $output .= '%';
      }
      return $output;
    }

    // No terms to compare against, return 0.
    if ($this->options['count_type'] == 0) {
      return '0';
    }
    elseif ($this->options['count_type'] == 2) {
      return '0' . (!empty($this->options['weight_suffix']) ? $this->options['weight_suffix'] : '');
    }
    else {
      return '0' . (!empty($this->options['percent_suffix']) ? '%' : '');
    }
  }

}
