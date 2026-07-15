<?php

declare(strict_types=1);

namespace Drupal\Tests\similarterms\Kernel;

use Drupal\Core\Config\FileStorage;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Entity\View;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Similar Terms on non-node entities via taxonomy_entity_index.
 *
 * When taxonomy_entity_index is installed, the handlers are exposed for all
 * content entity types and similarity is calculated from the
 * taxonomy_entity_index table instead of core's node-only taxonomy_index.
 *
 * @group similarterms
 *
 * @requires module taxonomy_entity_index
 */
#[Group('similarterms')]
class SimilarTermsEntityIndexTest extends SimilarTermsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'taxonomy_entity_index',
  ];

  /**
   * The test entities.
   *
   * @var \Drupal\entity_test\Entity\EntityTest[]
   */
  protected array $entities = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');

    // Create a taxonomy term reference field on entity_test.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_tags',
      'entity_type' => 'entity_test',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
      'cardinality' => -1,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
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

    // Load the entity_test view from the fixtures directory.
    $fixtures_path = \Drupal::service('extension.list.module')->getPath('similarterms') . '/tests/fixtures';
    $file_storage = new FileStorage($fixtures_path);
    $view_config = $file_storage->read('views.view.test_similar_terms_entity_index');
    if ($view_config) {
      View::create($view_config)->save();
    }

    $this->createTestEntities();
  }

  /**
   * {@inheritdoc}
   *
   * The taxonomy_entity_index insert hooks fire for every entity the parent
   * creates, terms included, so its schema and settings must be in place
   * before any content exists. This also indexes the parent's nodes on
   * creation.
   */
  protected function createTerms(): void {
    $this->installSchema('taxonomy_entity_index', ['taxonomy_entity_index']);
    $this->installConfig(['taxonomy_entity_index']);
    $this->config('taxonomy_entity_index.settings')
      ->set('types', ['node', 'entity_test'])
      ->save();
    parent::createTerms();
  }

  /**
   * Creates test entities mirroring the parent's node scenarios.
   *
   * The parent's nodes share the same terms and (typically) the same numeric
   * IDs as these entities, so any missing entity_type isolation in the index
   * queries would corrupt the counts asserted below.
   */
  protected function createTestEntities(): void {
    // Entity 1: Has terms PHP, Drupal.
    $this->entities['entity1'] = EntityTest::create([
      'type' => 'entity_test',
      'name' => 'Entity 1 - PHP and Drupal',
      'field_tags' => [
        ['target_id' => $this->terms['PHP']->id()],
        ['target_id' => $this->terms['Drupal']->id()],
      ],
    ]);
    $this->entities['entity1']->save();

    // Entity 2: Has terms PHP, Drupal, JavaScript (most similar to entity1).
    $this->entities['entity2'] = EntityTest::create([
      'type' => 'entity_test',
      'name' => 'Entity 2 - PHP, Drupal, JavaScript',
      'field_tags' => [
        ['target_id' => $this->terms['PHP']->id()],
        ['target_id' => $this->terms['Drupal']->id()],
        ['target_id' => $this->terms['JavaScript']->id()],
      ],
    ]);
    $this->entities['entity2']->save();

    // Entity 3: Has term JavaScript only (no overlap with entity1).
    $this->entities['entity3'] = EntityTest::create([
      'type' => 'entity_test',
      'name' => 'Entity 3 - JavaScript only',
      'field_tags' => [
        ['target_id' => $this->terms['JavaScript']->id()],
      ],
    ]);
    $this->entities['entity3']->save();

    // Entity 4: No terms at all.
    $this->entities['entity4'] = EntityTest::create([
      'type' => 'entity_test',
      'name' => 'Entity 4 - No terms',
      'field_tags' => [],
    ]);
    $this->entities['entity4']->save();
  }

  /**
   * Tests that handlers are registered for all content entity types.
   */
  public function testViewsDataRegistration(): void {
    $views_data = $this->container->get('views.views_data');

    // Non-node entity types get the handlers, keyed by their base table and
    // ID key.
    $entity_test_data = $views_data->get('entity_test');
    $this->assertArrayHasKey('similarterms', $entity_test_data);
    $this->assertSame('similar_terms_field', $entity_test_data['similarterms']['field']['id']);
    $this->assertSame('similar_terms_sort', $entity_test_data['similarterms']['sort']['id']);
    $this->assertArrayHasKey('similar_id', $entity_test_data);
    $this->assertSame('similar_terms_arg', $entity_test_data['similar_id']['argument']['id']);

    // The node handlers keep their original names for backwards
    // compatibility with existing views.
    $node_data = $views_data->get('node');
    $this->assertArrayHasKey('similarterms', $node_data);
    $this->assertArrayHasKey('similar_nid', $node_data);
    $this->assertSame('similar_terms_arg', $node_data['similar_nid']['argument']['id']);
  }

  /**
   * Tests the argument collects term IDs from the taxonomy_entity_index.
   */
  public function testArgumentCollectsTermsFromEntityIndex(): void {
    $view = Views::getView('test_similar_terms_entity_index');
    $view->setDisplay('default');
    $view->initHandlers();

    $result = $view->argument['similar_id']->validateArgument($this->entities['entity1']->id());
    $this->assertTrue($result, 'Argument validation passes for an entity with terms.');

    $tids = $view->argument['similar_id']->tids;
    $this->assertCount(2, $tids, 'Both term IDs collected from taxonomy_entity_index.');
    $this->assertArrayHasKey($this->terms['PHP']->id(), $tids);
    $this->assertArrayHasKey($this->terms['Drupal']->id(), $tids);
  }

  /**
   * Tests similarity results for non-node entities.
   */
  public function testEntityTestSimilarityResults(): void {
    $view = Views::getView('test_similar_terms_entity_index');
    $view->setDisplay('default');
    $view->setArguments([$this->entities['entity1']->id()]);
    $view->execute();

    $this->assertNotEmpty($view->result, 'View returns results.');

    $result_ids = array_map(fn($row) => $row->id, $view->result);

    // The argument entity is excluded, everything else is included via the
    // LEFT JOIN, matching or not.
    $this->assertNotContains($this->entities['entity1']->id(), $result_ids);
    $this->assertCount(3, $view->result);

    // Entity 2 shares both of entity 1's terms and must sort first.
    $this->assertEquals($this->entities['entity2']->id(), $view->result[0]->id);

    // Verify the similarity counts. The parent's nodes carry the same terms
    // and overlapping numeric IDs, so these counts are only correct when the
    // queries filter the index by entity_type.
    $view->initHandlers();
    $count_alias = $view->field['similarterms']->field_alias;
    $counts = [];
    foreach ($view->result as $row) {
      $counts[$row->id] = (int) $row->{$count_alias};
    }
    $this->assertSame(2, $counts[$this->entities['entity2']->id()], 'Entity sharing both terms counts 2.');
    $this->assertSame(0, $counts[$this->entities['entity3']->id()], 'Entity sharing no terms counts 0.');
    $this->assertSame(0, $counts[$this->entities['entity4']->id()], 'Entity without terms counts 0.');
  }

  /**
   * Tests node views keep working when taxonomy_entity_index is installed.
   */
  public function testNodeViewUsesEntityIndex(): void {
    $view = Views::getView('test_similar_terms');
    $view->setDisplay('default');
    $view->setArguments([$this->nodes['node1']->id()]);
    $view->execute();

    $this->assertNotEmpty($view->result, 'Node view returns results.');

    $result_nids = array_map(fn($row) => $row->nid, $view->result);
    $this->assertNotContains($this->nodes['node1']->id(), $result_nids, 'Argument node is excluded.');
    $this->assertContains($this->nodes['node2']->id(), $result_nids, 'Node with matching terms is in results.');

    // Node 2 shares both of node 1's terms and must sort first.
    $this->assertEquals($this->nodes['node2']->id(), $view->result[0]->nid);
  }

}
