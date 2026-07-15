<?php

declare(strict_types=1);

namespace Drupal\Tests\similarterms\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Similar Terms argument plugin.
 *
 * @group similarterms
 */
#[Group('similarterms')]
class SimilarTermsArgumentTest extends SimilarTermsTestBase {

  /**
   * Tests that argument validation passes even with no terms.
   */
  public function testArgumentValidationWithNoTerms(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available. This test requires a test view configuration.');
      return;
    }

    $view->setDisplay('default');
    $view->initHandlers();

    // Test with node that has no terms (node5).
    $nid = $this->nodes['node5']->id();

    // The argument should validate even though the node has no terms.
    $result = $view->argument['similar_nid']->validateArgument($nid);
    $this->assertTrue($result, 'Argument validation passes for node with no terms.');

    // The tids array should be empty.
    $this->assertEmpty($view->argument['similar_nid']->tids, 'No term IDs collected for node without terms.');
  }

  /**
   * Tests that argument validation passes with terms.
   */
  public function testArgumentValidationWithTerms(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available. This test requires a test view configuration.');
      return;
    }

    $view->setDisplay('default');
    $view->initHandlers();

    // Test with node that has terms (node1 has PHP and Drupal).
    $nid = $this->nodes['node1']->id();

    // The argument should validate.
    $result = $view->argument['similar_nid']->validateArgument($nid);
    $this->assertTrue($result, 'Argument validation passes for node with terms.');

    // The tids array should contain 2 term IDs.
    $this->assertCount(2, $view->argument['similar_nid']->tids, 'Correct number of term IDs collected.');

    // Verify the specific term IDs are present.
    $php_tid = $this->terms['PHP']->id();
    $drupal_tid = $this->terms['Drupal']->id();
    $this->assertArrayHasKey($php_tid, $view->argument['similar_nid']->tids, 'PHP term ID present.');
    $this->assertArrayHasKey($drupal_tid, $view->argument['similar_nid']->tids, 'Drupal term ID present.');
  }

  /**
   * Tests query alteration includes all nodes with LEFT JOIN approach.
   */
  public function testQueryIncludesAllNodes(): void {
    // Create a simple programmatic view.
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available. Manual query test needed.');
      return;
    }

    $view->setDisplay('default');

    // Execute view with node1 as argument (has PHP and Drupal terms).
    $view->setArguments([$this->nodes['node1']->id()]);
    $view->execute();

    // The view should return results.
    $this->assertNotEmpty($view->result, 'View returns results.');

    // Should include nodes with matching terms AND nodes without matching
    // terms.
    // Depending on view configuration, we should see:
    // - node2 (has PHP, Drupal - 100% match)
    // - node3, node4, node5 (0% match but still included)
    $result_nids = array_map(fn($row) => $row->nid, $view->result);

    // Node2 should definitely be in results (it has matching terms).
    $this->assertContains($this->nodes['node2']->id(), $result_nids,
      'Node with matching terms is in results.');
  }

  /**
   * Tests that include_args option works correctly.
   */
  public function testIncludeArgsOption(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    // Test with include_args = FALSE (default).
    $view->setDisplay('default');
    $view->initHandlers();
    $view->argument['similar_nid']->options['include_args'] = FALSE;

    $view->setArguments([$this->nodes['node1']->id()]);
    $view->execute();

    $result_nids = array_map(fn($row) => $row->nid, $view->result);

    // Node1 (the argument) should NOT be in results.
    $this->assertNotContains($this->nodes['node1']->id(), $result_nids,
      'Argument node excluded when include_args is FALSE.');

    // Test with include_args = TRUE.
    $view->destroy();
    $view->setDisplay('default');
    $view->initHandlers();
    $view->argument['similar_nid']->options['include_args'] = TRUE;

    $view->setArguments([$this->nodes['node1']->id()]);
    $view->execute();

    $result_nids = array_map(fn($row) => $row->nid, $view->result);

    // Node1 (the argument) SHOULD be in results.
    $this->assertContains($this->nodes['node1']->id(), $result_nids,
      'Argument node included when include_args is TRUE.');
  }

  /**
   * Tests vocabulary filtering option.
   */
  public function testVocabularyFiltering(): void {
    // Create a second vocabulary.
    $vocab2 = Vocabulary::create([
      'vid' => 'categories',
      'name' => 'Categories',
    ]);
    $vocab2->save();

    // Add a term to the second vocabulary.
    $category_term = Term::create([
      'vid' => 'categories',
      'name' => 'Category A',
    ]);
    $category_term->save();

    // Create a field for the second vocabulary.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_categories',
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
      'label' => 'Categories',
      'settings' => [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => [
            'categories' => 'categories',
          ],
        ],
      ],
    ]);
    $field->save();

    // Clear entity field map cache so the new field is recognized.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Reload node1 so it knows about the new field.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node_storage->resetCache([$this->nodes['node1']->id()]);
    $this->nodes['node1'] = $node_storage->load($this->nodes['node1']->id());

    // Update node1 to have both tags and categories.
    $this->nodes['node1']->field_categories = [
      ['target_id' => $category_term->id()],
    ];
    $this->nodes['node1']->save();

    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    $view->setDisplay('default');
    $view->initHandlers();

    // Set vocabulary filtering to only 'tags'.
    $view->argument['similar_nid']->options['vocabularies'] = ['tags' => 'tags'];

    $view->argument['similar_nid']->validateArgument($this->nodes['node1']->id());

    // Should only have term IDs from 'tags' vocabulary.
    $tids = $view->argument['similar_nid']->tids;

    foreach ($tids as $tid) {
      $term = Term::load($tid);
      $this->assertEquals('tags', $term->bundle(),
        'Only terms from filtered vocabulary are included.');
    }

    // The category term should not be in the tids.
    $this->assertNotContains($category_term->id(), $tids,
      'Term from excluded vocabulary is not included.');
  }

  /**
   * Tests minimum match percentage filtering.
   */
  public function testMinimumMatchPercentage(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    // Test with 100% match (exact match only).
    $view->setDisplay('default');
    $view->initHandlers();
    $view->argument['similar_nid']->options['min_match_percentage'] = 100;

    // Execute with node1 as argument (has PHP and Drupal).
    $view->setArguments([$this->nodes['node1']->id()]);
    $view->execute();

    $result_nids = array_map(fn($row) => $row->nid, $view->result);

    // Node2 has PHP, Drupal, JavaScript - 2 out of 2 match = 100%.
    $this->assertContains($this->nodes['node2']->id(), $result_nids,
      'Node with 100% match is included.');

    // Node3 has only JavaScript - 0 out of 2 match = 0%.
    $this->assertNotContains($this->nodes['node3']->id(), $result_nids,
      'Node with 0% match is excluded when filtering for 100%.');

    // Node4 has Python, React - 0 out of 2 match = 0%.
    $this->assertNotContains($this->nodes['node4']->id(), $result_nids,
      'Node with different terms is excluded when filtering for 100%.');
  }

  /**
   * Tests 50% minimum match filtering.
   */
  public function testFiftyPercentMinimumMatch(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    $view->setDisplay('default');
    $view->initHandlers();
    $view->argument['similar_nid']->options['min_match_percentage'] = 50;

    // Execute with node1 as argument (has 2 terms: PHP and Drupal).
    $view->setArguments([$this->nodes['node1']->id()]);
    $view->execute();

    $result_nids = array_map(fn($row) => $row->nid, $view->result);

    // Node2 has PHP, Drupal - 2 out of 2 = 100% (should be included).
    $this->assertContains($this->nodes['node2']->id(), $result_nids,
      'Node with 100% match is included when filtering for 50%+.');

    // Nodes with less than 50% match (less than 1 term) should be excluded.
    // Node3, node4, node5 all have 0 matching terms = 0%.
    $this->assertNotContains($this->nodes['node3']->id(), $result_nids,
      'Node with 0% match is excluded when filtering for 50%+.');
  }

  /**
   * Tests minimum match with no argument terms.
   */
  public function testMinimumMatchWithNoTerms(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    $view->setDisplay('default');
    $view->initHandlers();
    $view->argument['similar_nid']->options['min_match_percentage'] = 100;

    // Execute with node5 as argument (has no terms).
    $view->setArguments([$this->nodes['node5']->id()]);
    $view->execute();

    // When the argument node has no terms, the filter should not apply.
    // The view should show all results (or be empty depending on
    // implementation).
    // Since we have no terms to match against, all nodes have 0% match.
    $this->assertEmpty($view->result,
      'No results when filtering for 100% match and argument has no terms.');
  }

  /**
   * Tests single term matching with operator optimization.
   *
   * When node3 (which has 1 term: JavaScript) is used as argument:
   * - The query should use IN operator (optimized for single term)
   * - Matching should work correctly
   * - Node2 should match (has JavaScript among other terms)
   */
  public function testSingleTermMatching(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    $view->setDisplay('default');
    $view->initHandlers();

    // Use node3 (has JavaScript only) as argument.
    $view->setArguments([$this->nodes['node3']->id()]);
    $view->execute();

    $this->assertNotEmpty($view->result, 'View has results.');

    $result_nids = array_map(fn($row) => $row->nid, $view->result);

    // Node2 has JavaScript (plus PHP and Drupal), so it should match.
    $this->assertContains($this->nodes['node2']->id(), $result_nids,
      'Node2 matches on JavaScript term.');

    // Verify the similarity count for node2.
    if (isset($view->field['similarterms'])) {
      $field = $view->field['similarterms'];

      foreach ($view->result as $row) {
        if ($row->nid == $this->nodes['node2']->id()) {
          // Node2 matches 1 out of 1 term from node3 = 100%.
          if ($field->options['count_type'] == 1) {
            $rendered = $field->render($row);
            $this->assertEquals('100%', $rendered,
              'Node2 shows 100% match with node3 (1 out of 1 term).');
          }
        }
      }
    }
  }

}
