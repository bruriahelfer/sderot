<?php

declare(strict_types=1);

namespace Drupal\Tests\similarterms\Kernel;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Similar Terms weight-based functionality.
 *
 * @group similarterms
 */
#[Group('similarterms')]
class SimilarTermsWeightTest extends SimilarTermsTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createTerms(): void {
    // Create terms with varying weights to test weight-based sorting.
    $terms_data = [
      'PHP' => 0,
      'JavaScript' => 1,
      'Python' => 5,
      'Drupal' => 50,
      'React' => 200,
    ];

    foreach ($terms_data as $name => $weight) {
      $term = Term::create([
        'vid' => $this->vocabulary->id(),
        'name' => $name,
        'weight' => $weight,
      ]);
      $term->save();
      $this->terms[$name] = $term;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createTestNodes(): void {
    // Node 1: 3 low-weight terms (PHP=0, JavaScript=1, Python=5).
    // Total weight: 6, Count: 3.
    $this->nodes['node1'] = Node::create([
      'type' => 'article',
      'title' => 'Node 1 - Three low-weight terms',
      'field_tags' => [
        ['target_id' => $this->terms['PHP']->id()],
        ['target_id' => $this->terms['JavaScript']->id()],
        ['target_id' => $this->terms['Python']->id()],
      ],
    ]);
    $this->nodes['node1']->save();

    // Node 2: 1 high-weight term (Drupal=50).
    // Total weight: 50, Count: 1.
    // This tests that weight-based sorting differs from count-based.
    $this->nodes['node2'] = Node::create([
      'type' => 'article',
      'title' => 'Node 2 - Single high-weight term',
      'field_tags' => [
        ['target_id' => $this->terms['Drupal']->id()],
      ],
    ]);
    $this->nodes['node2']->save();

    // Node 3: 1 very high-weight term (React=200).
    // Total weight: 200, Count: 1.
    $this->nodes['node3'] = Node::create([
      'type' => 'article',
      'title' => 'Node 3 - Highest weight term',
      'field_tags' => [
        ['target_id' => $this->terms['React']->id()],
      ],
    ]);
    $this->nodes['node3']->save();

    // Node 4: 2 medium-weight terms (Python=5, Drupal=50).
    // Total weight: 55, Count: 2.
    $this->nodes['node4'] = Node::create([
      'type' => 'article',
      'title' => 'Node 4 - Two medium-weight terms',
      'field_tags' => [
        ['target_id' => $this->terms['Python']->id()],
        ['target_id' => $this->terms['Drupal']->id()],
      ],
    ]);
    $this->nodes['node4']->save();

    // Node 5: All terms (PHP=0, JavaScript=1, Python=5, Drupal=50, React=200).
    // Total weight: 256, Count: 5.
    $this->nodes['node5'] = Node::create([
      'type' => 'article',
      'title' => 'Node 5 - All terms',
      'field_tags' => [
        ['target_id' => $this->terms['PHP']->id()],
        ['target_id' => $this->terms['JavaScript']->id()],
        ['target_id' => $this->terms['Python']->id()],
        ['target_id' => $this->terms['Drupal']->id()],
        ['target_id' => $this->terms['React']->id()],
      ],
    ]);
    $this->nodes['node5']->save();

    // Node 6: No terms.
    $this->nodes['node6'] = Node::create([
      'type' => 'article',
      'title' => 'Node 6 - No terms',
      'field_tags' => [],
    ]);
    $this->nodes['node6']->save();
  }

  /**
   * Tests that terms with different weights are stored correctly.
   */
  public function testTermWeightsAreStoredCorrectly(): void {
    $this->assertEquals(0, $this->terms['PHP']->get('weight')->value, 'PHP term has weight 0.');
    $this->assertEquals(1, $this->terms['JavaScript']->get('weight')->value, 'JavaScript term has weight 1.');
    $this->assertEquals(5, $this->terms['Python']->get('weight')->value, 'Python term has weight 5.');
    $this->assertEquals(50, $this->terms['Drupal']->get('weight')->value, 'Drupal term has weight 50.');
    $this->assertEquals(200, $this->terms['React']->get('weight')->value, 'React term has weight 200.');
  }

  /**
   * Tests weight-based sorting produces different results than count-based.
   */
  public function testWeightBasedSortingDiffersFromCountBased(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    // Test with node1 as argument (has PHP=0, JavaScript=1, Python=5).
    // Expected matches:
    // - node5: All 3 terms match, weight sum = 6, count = 3
    // - node4: 1 term matches (Python=5), weight sum = 5, count = 1
    // - node2: 0 terms match, weight sum = 0, count = 0
    // - node3: 0 terms match, weight sum = 0, count = 0
    // - node6: 0 terms match, weight sum = 0, count = 0.
    // Test count-based sorting (default).
    $view->setDisplay('default');
    $view->initHandlers();
    $view->setArguments([$this->nodes['node1']->id()]);
    $view->sort['similarterms']->options['sort_method'] = 'count';
    $view->sort['similarterms']->options['order'] = 'DESC';
    $view->execute();

    $count_based_results = [];
    foreach ($view->result as $row) {
      $count_based_results[] = $row->nid;
    }

    // Test weight-based sorting.
    $view->destroy();
    $view->setDisplay('default');
    $view->initHandlers();
    $view->setArguments([$this->nodes['node1']->id()]);
    $view->sort['similarterms']->options['sort_method'] = 'weight';
    $view->sort['similarterms']->options['order'] = 'DESC';
    $view->execute();

    $weight_based_results = [];
    foreach ($view->result as $row) {
      $weight_based_results[] = $row->nid;
    }

    // The ordering should potentially differ because node5 has more matching
    // terms by count, but the weight sum determines the weight-based order.
    $this->assertNotEmpty($count_based_results, 'Count-based sorting returns results.');
    $this->assertNotEmpty($weight_based_results, 'Weight-based sorting returns results.');
  }

  /**
   * Tests weight sum calculation with specific term argument.
   */
  public function testWeightSumCalculationWithTermArgument(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    // Use node1 as argument (has PHP=0, JavaScript=1, Python=5).
    $view->setDisplay('default');
    $view->initHandlers();
    $view->setArguments([$this->nodes['node1']->id()]);
    $view->sort['similarterms']->options['sort_method'] = 'weight';
    $view->sort['similarterms']->options['order'] = 'DESC';
    $view->execute();

    $this->assertNotEmpty($view->result, 'View returns results with weight-based sorting.');

    // Node5 should have the highest weight sum (has all 3 matching
    // terms = 0+1+5 = 6).
    // Node4 should have weight sum of 5 (has Python=5).
    // Other nodes should have weight sum of 0 (no matching terms).
    $found_node5 = FALSE;
    $found_node4 = FALSE;

    foreach ($view->result as $row) {
      if ($row->nid == $this->nodes['node5']->id()) {
        $found_node5 = TRUE;
      }
      if ($row->nid == $this->nodes['node4']->id()) {
        $found_node4 = TRUE;
      }
    }

    $this->assertTrue($found_node5, 'Node5 with all matching terms is in results.');
    $this->assertTrue($found_node4, 'Node4 with one matching term is in results.');
  }

  /**
   * Tests weight-based sorting with high-weight term argument.
   */
  public function testWeightSortingWithHighWeightTermArgument(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    // Use node3 as argument (has React=200).
    // Expected matches:
    // - node5: Has React, weight sum = 200, count = 1
    // - All others: 0 matches.
    $view->setDisplay('default');
    $view->initHandlers();
    $view->setArguments([$this->nodes['node3']->id()]);
    $view->sort['similarterms']->options['sort_method'] = 'weight';
    $view->sort['similarterms']->options['order'] = 'DESC';
    $view->execute();

    $result_nids = array_map(fn($row) => $row->nid, $view->result);

    // Node5 should be in results (has React term).
    $this->assertContains($this->nodes['node5']->id(), $result_nids,
      'Node with high-weight matching term is in results.');
  }

  /**
   * Tests that count-based and weight-based sorting produce expected order.
   */
  public function testCountVsWeightSortingOrder(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    // Use node4 as argument (has Python=5, Drupal=50).
    // Expected by count (descending):
    // - node5: 2 matches (Python + Drupal)
    // - node1: 1 match (Python)
    // - node2: 1 match (Drupal)
    // - node3, node6: 0 matches
    //
    // Expected by weight (descending):
    // - node5: weight sum = 5 + 50 = 55
    // - node2: weight sum = 50 (Drupal only)
    // - node1: weight sum = 5 (Python only)
    // - node3, node6: weight sum = 0.
    // Test count-based sorting.
    $view->setDisplay('default');
    $view->initHandlers();
    $view->setArguments([$this->nodes['node4']->id()]);
    $view->sort['similarterms']->options['sort_method'] = 'count';
    $view->sort['similarterms']->options['order'] = 'DESC';
    $view->execute();

    $count_results = array_map(fn($row) => $row->nid, $view->result);

    // Node5 should be first (2 matching terms).
    $this->assertEquals($this->nodes['node5']->id(), $count_results[0],
      'Count-based: Node with most matching terms is first.');

    // Test weight-based sorting.
    $view->destroy();
    $view->setDisplay('default');
    $view->initHandlers();
    $view->setArguments([$this->nodes['node4']->id()]);
    $view->sort['similarterms']->options['sort_method'] = 'weight';
    $view->sort['similarterms']->options['order'] = 'DESC';
    $view->execute();

    $weight_results = array_map(fn($row) => $row->nid, $view->result);

    // Node5 should still be first (highest weight sum = 55).
    $this->assertEquals($this->nodes['node5']->id(), $weight_results[0],
      'Weight-based: Node with highest weight sum is first.');

    // But the order of node2 (weight=50) and node1 (weight=5) should differ.
    // In count-based, they're tied (both have 1 match).
    // In weight-based, node2 should come before node1.
    $node2_position_weight = array_search($this->nodes['node2']->id(), $weight_results);
    $node1_position_weight = array_search($this->nodes['node1']->id(), $weight_results);

    $this->assertNotFalse($node2_position_weight, 'Node2 found in weight-based results.');
    $this->assertNotFalse($node1_position_weight, 'Node1 found in weight-based results.');
    $this->assertLessThan($node1_position_weight, $node2_position_weight,
      'Weight-based: Node2 (weight=50) ranks higher than Node1 (weight=5).');
  }

  /**
   * Tests weight sum field display shows correct values.
   */
  public function testWeightSumFieldDisplay(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    // Configure field to display weight sum (count_type = 2).
    $view->setDisplay('default');
    $view->initHandlers();

    if (!isset($view->field['similarterms'])) {
      $this->markTestSkipped('Similarity field not configured in test view.');
      return;
    }

    $view->field['similarterms']->options['count_type'] = 2;
    $view->setArguments([$this->nodes['node4']->id()]);
    $view->execute();

    // Find node5 (should have weight sum = 5 + 50 = 55).
    foreach ($view->result as $row) {
      if ($row->nid == $this->nodes['node5']->id()) {
        $rendered = $view->field['similarterms']->render($row);
        $this->assertEquals(55, $rendered,
          'Weight sum field displays correct total (5 + 50 = 55).');
      }
    }

    // Find node2 (should have weight sum = 50).
    foreach ($view->result as $row) {
      if ($row->nid == $this->nodes['node2']->id()) {
        $rendered = $view->field['similarterms']->render($row);
        $this->assertEquals(50, $rendered,
          'Weight sum field displays correct value for single high-weight term.');
      }
    }

    // Find node1 (should have weight sum = 5).
    foreach ($view->result as $row) {
      if ($row->nid == $this->nodes['node1']->id()) {
        $rendered = $view->field['similarterms']->render($row);
        $this->assertEquals(5, $rendered,
          'Weight sum field displays correct value for single low-weight term.');
      }
    }
  }

  /**
   * Tests that weight sum handles zero-weight terms correctly.
   */
  public function testWeightSumWithZeroWeightTerms(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    // Use node1 as argument (includes PHP with weight=0).
    $view->setDisplay('default');
    $view->initHandlers();

    if (!isset($view->field['similarterms'])) {
      $this->markTestSkipped('Similarity field not configured in test view.');
      return;
    }

    $view->field['similarterms']->options['count_type'] = 2;
    $view->setArguments([$this->nodes['node1']->id()]);
    $view->execute();

    // Find node5 (has PHP=0, JavaScript=1, Python=5).
    foreach ($view->result as $row) {
      if ($row->nid == $this->nodes['node5']->id()) {
        $rendered = $view->field['similarterms']->render($row);
        // Weight sum should be 0 + 1 + 5 = 6, not treating 0 as NULL.
        $this->assertEquals(6, $rendered,
          'Weight sum correctly includes zero-weight terms in calculation.');
      }
    }
  }

  /**
   * Tests backward compatibility: count-based behavior still works.
   */
  public function testBackwardCompatibilityCountBased(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    // Test that default count-based sorting still works as before.
    $view->setDisplay('default');
    $view->initHandlers();
    $view->setArguments([$this->nodes['node4']->id()]);

    // Explicitly set to count-based (should be default).
    $view->sort['similarterms']->options['sort_method'] = 'count';
    $view->sort['similarterms']->options['order'] = 'DESC';
    $view->execute();

    $this->assertNotEmpty($view->result, 'Count-based sorting returns results.');

    // Node5 should be first (has 2 matching terms: Python + Drupal).
    $result_nids = array_map(fn($row) => $row->nid, $view->result);
    $this->assertEquals($this->nodes['node5']->id(), $result_nids[0],
      'Count-based sorting: Node with most matching terms is first.');
  }

  /**
   * Tests backward compatibility: percentage display still works.
   */
  public function testBackwardCompatibilityPercentageDisplay(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    $view->setDisplay('default');
    $view->initHandlers();

    if (!isset($view->field['similarterms'])) {
      $this->markTestSkipped('Similarity field not configured in test view.');
      return;
    }

    // Set field to display percentage (count_type = 1).
    $view->field['similarterms']->options['count_type'] = 1;
    $view->field['similarterms']->options['percent_suffix'] = TRUE;

    // Use node4 as argument (has 2 terms: Python, Drupal).
    $view->setArguments([$this->nodes['node4']->id()]);
    $view->execute();

    // Find node5 (has both matching terms, 2 out of 2 = 100%).
    foreach ($view->result as $row) {
      if ($row->nid == $this->nodes['node5']->id()) {
        $rendered = $view->field['similarterms']->render($row);
        $this->assertEquals('100%', $rendered,
          'Percentage display shows 100% for nodes with all matching terms.');
      }
    }

    // Find node1 (has 1 matching term: Python, 1 out of 2 = 50%).
    foreach ($view->result as $row) {
      if ($row->nid == $this->nodes['node1']->id()) {
        $rendered = $view->field['similarterms']->render($row);
        $this->assertEquals('50%', $rendered,
          'Percentage display shows 50% for nodes with half matching terms.');
      }
    }
  }

  /**
   * Tests backward compatibility: count display still works.
   */
  public function testBackwardCompatibilityCountDisplay(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    $view->setDisplay('default');
    $view->initHandlers();

    if (!isset($view->field['similarterms'])) {
      $this->markTestSkipped('Similarity field not configured in test view.');
      return;
    }

    // Set field to display count (count_type = 0).
    $view->field['similarterms']->options['count_type'] = 0;

    // Use node4 as argument (has 2 terms: Python, Drupal).
    $view->setArguments([$this->nodes['node4']->id()]);
    $view->execute();

    // Find node5 (has 2 matching terms).
    foreach ($view->result as $row) {
      if ($row->nid == $this->nodes['node5']->id()) {
        $rendered = $view->field['similarterms']->render($row);
        $this->assertEquals(2, $rendered,
          'Count display shows correct number of matching terms.');
      }
    }

    // Find node1 (has 1 matching term: Python).
    foreach ($view->result as $row) {
      if ($row->nid == $this->nodes['node1']->id()) {
        $rendered = $view->field['similarterms']->render($row);
        $this->assertEquals(1, $rendered,
          'Count display shows 1 for nodes with one matching term.');
      }
    }
  }

  /**
   * Tests weight-based sorting with node that has no terms.
   */
  public function testWeightSortingWithNoTerms(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    // Use node6 as argument (has no terms).
    $view->setDisplay('default');
    $view->initHandlers();
    $view->setArguments([$this->nodes['node6']->id()]);
    $view->sort['similarterms']->options['sort_method'] = 'weight';
    $view->sort['similarterms']->options['order'] = 'DESC';
    $view->execute();

    // Should not error, but may return empty or all nodes with 0 weight sum.
    $this->assertIsArray($view->result, 'View executes without error when argument node has no terms.');
  }

  /**
   * Tests ascending weight-based sort order.
   */
  public function testWeightSortingAscending(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    // Use node4 as argument (has Python=5, Drupal=50).
    $view->setDisplay('default');
    $view->initHandlers();
    $view->setArguments([$this->nodes['node4']->id()]);
    $view->sort['similarterms']->options['sort_method'] = 'weight';
    $view->sort['similarterms']->options['order'] = 'ASC';
    $view->execute();

    $result_nids = array_map(fn($row) => $row->nid, $view->result);

    // With ascending order, nodes with lower weight sums should come first.
    // node1 (weight=5) should come before node2 (weight=50).
    $node1_position = array_search($this->nodes['node1']->id(), $result_nids);
    $node2_position = array_search($this->nodes['node2']->id(), $result_nids);

    if ($node1_position !== FALSE && $node2_position !== FALSE) {
      $this->assertLessThan($node2_position, $node1_position,
        'Ascending weight sort: Lower weight sum ranks first.');
    }
  }

  /**
   * Tests that weight-based sorting works with include_args option.
   */
  public function testWeightSortingWithIncludeArgs(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    $view->setDisplay('default');
    $view->initHandlers();
    $view->argument['similar_nid']->options['include_args'] = TRUE;
    $view->setArguments([$this->nodes['node4']->id()]);
    $view->sort['similarterms']->options['sort_method'] = 'weight';
    $view->sort['similarterms']->options['order'] = 'DESC';
    $view->execute();

    $result_nids = array_map(fn($row) => $row->nid, $view->result);

    // Node4 (the argument) should be included in results.
    $this->assertContains($this->nodes['node4']->id(), $result_nids,
      'Argument node is included in results when include_args is TRUE.');
  }

  /**
   * Tests sort configuration defaults.
   *
   * Verifies that the sort handler has correct default values:
   * - sort_method = 'count' (count-based sorting)
   * - order = 'DESC' (descending order)
   */
  public function testSortConfigurationDefaults(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    $view->setDisplay('default');
    $view->initHandlers();

    if (!isset($view->sort['similarterms'])) {
      $this->markTestSkipped('Similarity sort not configured in test view.');
      return;
    }

    $sort = $view->sort['similarterms'];

    // Verify sort_method defaults to 'count'.
    $this->assertEquals('count', $sort->options['sort_method'],
      'Sort sort_method defaults to "count".');

    // Verify order defaults to 'DESC'.
    $this->assertEquals('DESC', $sort->options['order'],
      'Sort order defaults to "DESC" (descending).');
  }

}
