<?php

declare(strict_types=1);

namespace Drupal\Tests\similarterms\Kernel;

use Drupal\views\ResultRow;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Similar Terms field plugin.
 *
 * @group similarterms
 */
#[Group('similarterms')]
class SimilarTermsFieldTest extends SimilarTermsTestBase {

  /**
   * Tests field rendering with count display type.
   */
  public function testFieldRenderingWithCount(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    $view->setDisplay('default');
    $view->initHandlers();

    // Set field to display count.
    if (isset($view->field['similarterms'])) {
      $view->field['similarterms']->options['count_type'] = 0;
    }

    // Execute view with node1 as argument.
    $view->setArguments([$this->nodes['node1']->id()]);
    $view->execute();

    $this->assertNotEmpty($view->result, 'View has results.');

    // Find node2 in results (should have 2 matching terms).
    foreach ($view->result as $row) {
      if ($row->nid == $this->nodes['node2']->id()) {
        $rendered = $view->field['similarterms']->render($row);
        // Node2 has PHP and Drupal, matching node1's terms.
        $this->assertEquals(2, $rendered, 'Count of matching terms is correct.');
      }
    }
  }

  /**
   * Tests field rendering with percentage display type.
   */
  public function testFieldRenderingWithPercentage(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    $view->setDisplay('default');
    $view->initHandlers();

    // Set field to display percentage.
    if (isset($view->field['similarterms'])) {
      $view->field['similarterms']->options['count_type'] = 1;
      $view->field['similarterms']->options['percent_suffix'] = TRUE;
    }

    // Execute view with node1 as argument (has 2 terms: PHP, Drupal).
    $view->setArguments([$this->nodes['node1']->id()]);
    $view->execute();

    $this->assertNotEmpty($view->result, 'View has results.');

    // Find node2 in results (should have 100% match - 2 out of 2 terms).
    foreach ($view->result as $row) {
      if ($row->nid == $this->nodes['node2']->id()) {
        $rendered = $view->field['similarterms']->render($row);
        // 2 matching terms / 2 total terms = 100%.
        $this->assertEquals('100%', $rendered, 'Percentage with suffix is correct.');
      }
    }
  }

  /**
   * Tests field rendering with zero similarity.
   */
  public function testFieldRenderingWithZeroSimilarity(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    $view->setDisplay('default');
    $view->initHandlers();

    // Set field to display count.
    if (isset($view->field['similarterms'])) {
      $view->field['similarterms']->options['count_type'] = 0;
    }

    // Execute view with node1 as argument.
    $view->setArguments([$this->nodes['node1']->id()]);
    $view->execute();

    // Find node4 or node5 in results (should have 0 matching terms).
    foreach ($view->result as $row) {
      if ($row->nid == $this->nodes['node4']->id()) {
        $rendered = $view->field['similarterms']->render($row);
        // Node4 has Python and React, no overlap with node1's PHP and Drupal.
        $this->assertEquals(0, $rendered, 'Zero similarity count is handled correctly.');
      }
    }
  }

  /**
   * Tests field rendering when argument node has no terms.
   */
  public function testFieldRenderingWithNoArgumentTerms(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    $view->setDisplay('default');
    $view->initHandlers();

    // Set field to display percentage.
    if (isset($view->field['similarterms'])) {
      $view->field['similarterms']->options['count_type'] = 1;
      $view->field['similarterms']->options['percent_suffix'] = TRUE;
    }

    // Execute view with node5 as argument (has no terms).
    $view->setArguments([$this->nodes['node5']->id()]);
    $view->execute();

    // All results should show 0% since there are no terms to compare against.
    foreach ($view->result as $row) {
      $rendered = $view->field['similarterms']->render($row);
      $this->assertEquals('0%', $rendered,
        'Field renders 0% when argument node has no terms.');
    }
  }

  /**
   * Tests percentage rendering without suffix.
   */
  public function testFieldRenderingPercentageWithoutSuffix(): void {
    $view = Views::getView('test_similar_terms');

    if (!$view) {
      $this->markTestSkipped('Test view not available.');
      return;
    }

    $view->setDisplay('default');
    $view->initHandlers();

    // Set field to display percentage without suffix.
    if (isset($view->field['similarterms'])) {
      $view->field['similarterms']->options['count_type'] = 1;
      $view->field['similarterms']->options['percent_suffix'] = FALSE;
    }

    // Execute view with node1 as argument.
    $view->setArguments([$this->nodes['node1']->id()]);
    $view->execute();

    // Find node2 in results.
    foreach ($view->result as $row) {
      if ($row->nid == $this->nodes['node2']->id()) {
        $rendered = $view->field['similarterms']->render($row);
        // Should be numeric 100, not "100%".
        $this->assertEquals(100, $rendered, 'Percentage without suffix is correct.');
      }
    }
  }

  /**
   * Tests that field handles null values safely.
   */
  public function testFieldHandlesNullValuesSafely(): void {
    // Create a mock result row with null field value.
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

    $field = $view->field['similarterms'];
    $field->options['count_type'] = 0;

    // Create a mock row.
    $row = new ResultRow();
    $row->nid = 999;
    // Don't set the field alias property, simulating a null value.
    $rendered = $field->render($row);

    // Should return 0, not error.
    $this->assertEquals(0, $rendered,
      'Field safely handles null values by returning 0.');
  }

  /**
   * Tests field configuration defaults.
   *
   * Verifies that the field handler has correct default values:
   * - count_type = 1 (percentage display)
   * - percent_suffix = 1 (show % symbol)
   */
  public function testFieldConfigurationDefaults(): void {
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

    $field = $view->field['similarterms'];

    // Verify count_type defaults to 1 (percentage).
    $this->assertEquals(1, $field->options['count_type'],
      'Field count_type defaults to 1 (percentage display).');

    // Verify percent_suffix defaults to 1 (show %).
    $this->assertEquals(1, $field->options['percent_suffix'],
      'Field percent_suffix defaults to 1 (show % symbol).');

    // Also verify that weight_suffix has an empty default.
    $this->assertEquals('', $field->options['weight_suffix'] ?? '',
      'Field weight_suffix defaults to empty string.');
  }

}
