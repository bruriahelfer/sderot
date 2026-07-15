<?php

declare(strict_types=1);

namespace Drupal\Tests\similarterms\Kernel;

use Drupal\Core\Config\FileStorage;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\views\Entity\View;

/**
 * Base class for Similar Terms kernel tests.
 */
abstract class SimilarTermsTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'system',
    'taxonomy',
    'text',
    'user',
    'views',
    'similarterms',
  ];

  /**
   * The vocabulary to use for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * The taxonomy terms.
   *
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected array $terms = [];

  /**
   * The test nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected array $nodes = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['field', 'node', 'taxonomy', 'views']);

    // Load the test view from fixtures directory.
    $fixtures_path = \Drupal::service('extension.list.module')->getPath('similarterms') . '/tests/fixtures';
    $file_storage = new FileStorage($fixtures_path);
    $view_config = $file_storage->read('views.view.test_similar_terms');
    if ($view_config) {
      View::create($view_config)->save();
    }

    // Create a content type.
    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type->save();

    // Create a vocabulary.
    $this->vocabulary = Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
    ]);
    $this->vocabulary->save();

    // Create a taxonomy term reference field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_tags',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
      'cardinality' => -1,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'Tags',
      'settings' => [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => [
            'tags' => 'tags',
          ],
        ],
      ],
    ]);
    $field->save();

    // Create taxonomy terms.
    $this->createTerms();

    // Create test nodes with various term associations.
    $this->createTestNodes();
  }

  /**
   * Creates taxonomy terms for testing.
   */
  protected function createTerms(): void {
    $term_names = ['PHP', 'JavaScript', 'Python', 'Drupal', 'React'];
    foreach ($term_names as $name) {
      $term = Term::create([
        'vid' => $this->vocabulary->id(),
        'name' => $name,
      ]);
      $term->save();
      $this->terms[$name] = $term;
    }
  }

  /**
   * Creates test nodes with various term combinations.
   */
  protected function createTestNodes(): void {
    // Node 1: Has terms PHP, Drupal.
    $this->nodes['node1'] = Node::create([
      'type' => 'article',
      'title' => 'Node 1 - PHP and Drupal',
      'field_tags' => [
        ['target_id' => $this->terms['PHP']->id()],
        ['target_id' => $this->terms['Drupal']->id()],
      ],
    ]);
    $this->nodes['node1']->save();

    // Node 2: Has terms PHP, Drupal, JavaScript (most similar to node1).
    $this->nodes['node2'] = Node::create([
      'type' => 'article',
      'title' => 'Node 2 - PHP, Drupal, JavaScript',
      'field_tags' => [
        ['target_id' => $this->terms['PHP']->id()],
        ['target_id' => $this->terms['Drupal']->id()],
        ['target_id' => $this->terms['JavaScript']->id()],
      ],
    ]);
    $this->nodes['node2']->save();

    // Node 3: Has term JavaScript only (partially similar to node1).
    $this->nodes['node3'] = Node::create([
      'type' => 'article',
      'title' => 'Node 3 - JavaScript only',
      'field_tags' => [
        ['target_id' => $this->terms['JavaScript']->id()],
      ],
    ]);
    $this->nodes['node3']->save();

    // Node 4: Has terms Python, React (not similar to node1).
    $this->nodes['node4'] = Node::create([
      'type' => 'article',
      'title' => 'Node 4 - Python and React',
      'field_tags' => [
        ['target_id' => $this->terms['Python']->id()],
        ['target_id' => $this->terms['React']->id()],
      ],
    ]);
    $this->nodes['node4']->save();

    // Node 5: No terms at all.
    $this->nodes['node5'] = Node::create([
      'type' => 'article',
      'title' => 'Node 5 - No terms',
      'field_tags' => [],
    ]);
    $this->nodes['node5']->save();
  }

}
