<?php

namespace Drupal\similarterms\Plugin\views\argument;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Drupal\views\Plugin\views\join\Standard;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a node id.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("similar_terms_arg")
 */
class SimilarTermsArgument extends NumericArgument implements ContainerFactoryPluginInterface {

  /**
   * Database Service Object.
   */
  protected Connection $connection;

  /**
   * The vocabulary storage.
   */
  protected VocabularyStorageInterface $vocabularyStorage;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The term ids.
   *
   * @var int[]
   */
  public array $tids;

  /**
   * Constructs the SimilarTermsArgument object.
   *
   * @param string[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string[] $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage
   *   The vocabulary storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, Connection $connection, VocabularyStorageInterface $vocabulary_storage, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->vocabularyStorage = $vocabulary_storage;
    $this->connection = $connection;
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
      $container->get('database'),
      $container->get('entity_type.manager')->getStorage('taxonomy_vocabulary'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Define default values for options.
   *
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['vocabularies'] = ['default' => []];
    $options['include_args'] = ['default' => FALSE];
    $options['min_match_percentage'] = ['default' => 0];

    return $options;
  }

  /**
   * Build options settings form.
   *
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {

    parent::buildOptionsForm($form, $form_state);
    $vocabularies = [];
    $result = $this->vocabularyStorage->loadMultiple();

    foreach ($result as $vocabulary) {
      $vocabularies[$vocabulary->id()] = $vocabulary->label();
    }

    $form['vocabularies'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Limit similarity to terms within these vocabularies'),
      '#description' => $this->t('Choosing any vocabularies here will limit the terms used to calculate similarity. It is usually best NOT to limit the terms, but in some cases this is necessary. Leave all checkboxes unselected to not limit terms.'),
      '#options' => $vocabularies,
      '#default_value' => empty($this->options['vocabularies']) ? [] : $this->options['vocabularies'],
    ];

    $entity_type_label = $this->entityTypeManager->getDefinition($this->getEntityType())->getLabel();
    $form['include_args'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include argument @type(s) in results', ['@type' => $entity_type_label]),
      '#description' => $this->t('If selected, the @type(s) passed as the argument will be included in the view results.', ['@type' => $entity_type_label]),
      '#default_value' => !empty($this->options['include_args']),
    ];

    $form['min_match_percentage'] = [
      '#type' => 'select',
      '#title' => $this->t('Minimum similarity match'),
      '#description' => $this->t('Filter results to only show nodes that match at least this percentage of terms. Use "100% (Exact match)" to show only nodes that share all the same terms.'),
      '#options' => [
        0 => $this->t('No minimum (show all)'),
        25 => $this->t('25% or more'),
        50 => $this->t('50% or more'),
        75 => $this->t('75% or more'),
        100 => $this->t('100% (Exact match only)'),
      ],
      '#default_value' => $this->options['min_match_percentage'] ?? 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // Remove elements that are not selected.
    $form_state->setValue(['options', 'vocabularies'],
      array_filter($form_state->getValue(['options', 'vocabularies'])));
    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * Validate this argument works. By default, all arguments are valid.
   *
   * {@inheritdoc}
   */
  public function validateArgument($arg) {

    if (isset($this->argument_validated)) {
      return $this->argument_validated;
    }

    $this->value = [$arg => $arg];
    $vocabulary_vids = empty($this->options['vocabularies']) ? [] : array_filter($this->options['vocabularies']);

    $use_entity_index = $this->getModuleHandler()->moduleExists('taxonomy_entity_index');
    $index_table = $use_entity_index ? 'taxonomy_entity_index' : 'taxonomy_index';
    $select = $this->connection->select($index_table, 'ti')->fields('ti', ['tid']);
    if (count($vocabulary_vids)) {
      $select->join('taxonomy_term_data', 'td', 'ti.tid = td.tid');
      $select->condition('td.vid', $vocabulary_vids, 'IN');
    }
    if ($use_entity_index) {
      $select->condition('ti.entity_id', $this->value, 'IN');
      $select->condition('ti.entity_type', $this->getEntityType());
    }
    else {
      $select->condition('ti.nid', $this->value, 'IN');
    }
    $result = $select->execute();

    $this->tids = [];
    foreach ($result as $row) {
      $this->tids[$row->tid] = $row->tid;
    }
    $this->view->tids = $this->tids;

    // Allow validation to pass even if no terms are found.
    // This enables displaying all content sorted by similarity (including 0).
    return TRUE;
  }

  /**
   * Add filter(s).
   *
   * {@inheritdoc}
   */
  public function query($group_by = FALSE): void {
    $this->ensureMyTable();

    $entity_type_id = $this->getEntityType();
    $id_key = $this->entityTypeManager->getDefinition($entity_type_id)->getKey('id');
    $use_entity_index = $this->getModuleHandler()->moduleExists('taxonomy_entity_index');

    // Use LEFT JOIN instead of INNER JOIN to include entities with 0 matching
    // terms. This allows displaying all content sorted by similarity.
    if (!empty($this->tids)) {
      $tids = array_values($this->tids);

      // Determine operator based on number of values.
      // When there's only one value, '=' works better than 'IN' in JOIN extras.
      $operator = count($tids) === 1 ? '=' : 'IN';
      $value = count($tids) === 1 ? $tids[0] : $tids;

      $extra = [
        [
          'field' => 'tid',
          'value' => $value,
          'operator' => $operator,
        ],
      ];
      if ($use_entity_index) {
        // The taxonomy_entity_index table indexes all entity types, so the
        // join must be restricted to rows for this entity type.
        $extra[] = [
          'field' => 'entity_type',
          'value' => $entity_type_id,
          'operator' => '=',
        ];
      }
      $configuration = [
        'type' => 'LEFT',
        'table' => $use_entity_index ? 'taxonomy_entity_index' : 'taxonomy_index',
        'field' => $use_entity_index ? 'entity_id' : 'nid',
        'left_table' => $this->tableAlias,
        'left_field' => $id_key,
        'operator' => '=',
        'extra' => $extra,
      ];
      $join = new Standard($configuration, 'similarterms_taxonomy_index', $configuration['table']);
      $this->query->addRelationship('similarterms_taxonomy_index', $join, $this->tableAlias);
    }

    // Exclude the current entity/entities passed as the argument.
    if (empty($this->options['include_args'])) {
      $this->query->addWhere(0, $this->tableAlias . '.' . $id_key, $this->value, 'NOT IN');
    }

    // Ensure the entity ID is accessible on result rows with the expected
    // alias. Add field first so it gets the correct alias.
    $id_alias = $this->query->addField($this->tableAlias, $id_key, $id_key);

    // Group by the field alias to avoid duplicate GROUP BY entries.
    // This prevents both the qualified column and its alias being in
    // GROUP BY.
    $this->query->addGroupBy($id_alias);

    // Apply minimum match percentage filter if set.
    $min_percentage = $this->options['min_match_percentage'] ?? 0;
    if ($min_percentage > 0) {
      if (!empty($this->tids)) {
        $total_terms = count($this->tids);
        // Calculate minimum number of matching terms needed.
        $min_matches = ceil($total_terms * ($min_percentage / 100));

        // Add HAVING clause to filter by match count.
        // Use the same count expression that the field and sort handlers use.
        $this->query->addHavingExpression(0, "COUNT(DISTINCT similarterms_taxonomy_index.tid) >= :min_matches", [
          ':min_matches' => $min_matches,
        ]);
      }
      else {
        // If the argument node has no terms and a minimum match is required,
        // ensure no results are returned (since 0% match < any minimum).
        $this->query->addWhereExpression(0, '1 = 0');
      }
    }
  }

}
