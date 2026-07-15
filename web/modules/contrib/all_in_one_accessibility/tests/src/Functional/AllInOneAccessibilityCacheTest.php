<?php

namespace Drupal\Tests\all_in_one_accessibility\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the all in one accessibility cache rebuild race condition.
 *
 * @group all_in_one_accessibility
 */

class AllInOneAccessibilityCacheTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['all_in_one_accessibility'];

  /**
   * Tests that the library is correctly attached despite cache rebuilt on admin pages.
   */
  public function testLibraryCacheRebuildContext() {
    $admin_user = $this->drupalCreateUser(['access administration pages', 'administer site configuration']);
    
    // Visit the front page.
    $this->drupalGet('<front>');
    $this->assertSession()->responseContains('aioa-adawidget');
    
    // Clear the cache from an administrative route.
    // This perfectly emulates the race condition mentioned in the issue,
    // where the library info is rebuilt and cached while on an admin page.
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/development/performance');
    $this->submitForm([], 'Clear all caches');
    
    // Logout and visit the front page again to assert the library isn't lost.
    $this->drupalLogout();
    $this->drupalGet('<front>');
    
    // The library and Javascript should still be present!
    $this->assertSession()->responseContains('aioa-adawidget');
  }

}