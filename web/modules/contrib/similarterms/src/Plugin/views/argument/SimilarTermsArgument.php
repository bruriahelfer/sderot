<?php

namespace Drupal\similarterms\Plugin\views\argument;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
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
   * The term ids.
   *
   * @var int[]
   */
  protected array $tids;

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
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, Connection $connection, VocabularyStorageInterface $vocabulary_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->vocabularyStorage = $vocabulary_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition, $container->get('database'), $container->get('entity_type.manager')->getStorage('taxonomy_vocabulary')
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

    $form['include_args'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include argument node(s) in results'),
      '#description' => $this->t('If selected, the node(s) passed as the argument will be included in the view results.'),
      '#default_value' => !empty($this->options['include_args']),
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
    $vocabulary_vids = empty($this->options['vocabularies']) ? [] : $this->options['vocabularies'];

    foreach ($vocabulary_vids as $key => $val) {
      if ($val === 0) {
        unset($vocabulary_vids[$key]);
      }
    }

    $select = $this->connection->select('taxonomy_index', 'ti')->fields('ti', ['tid']);
    if (count($vocabulary_vids)) {
      $select->join('taxonomy_term_data', 'td', 'ti.tid = td.tid');
      $select->condition('td.vid', $vocabulary_vids, 'IN');
    }
    $select->condition('ti.nid', $this->value, 'IN');
    $result = $select->execute();

    $this->tids = [];
    foreach ($result as $row) {
      $this->tids[$row->tid] = $row->tid;
    }
    $this->view->tids = $this->tids;

    if (count($this->tids) == 0) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Add filter(s).
   *
   * {@inheritdoc}
   */
  public function query($group_by = FALSE): void {
    $this->ensureMyTable();

    $this->query->addTable('taxonomy_index', NULL, NULL, 'similarterms_taxonomy_index');
    $this->query->addWhere(0, "similarterms_taxonomy_index.tid", $this->tids, 'IN');

    // Exclude the current node(s)
    if (empty($this->options['include_args'])) {
      $this->query->addWhere(0, "node.nid", $this->value, 'NOT IN');
    }
    $this->query->addGroupBy('nid');
  }

}
