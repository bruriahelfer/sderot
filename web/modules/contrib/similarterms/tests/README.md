# Similar By Terms - Tests

This directory contains automated tests for the Similar By Terms module.

## Test Structure

```
tests/
├── src/
│   └── Kernel/
│       ├── SimilarTermsTestBase.php       # Base class for kernel tests
│       ├── SimilarTermsArgumentTest.php   # Tests for argument handler
│       ├── SimilarTermsFieldTest.php      # Tests for field handler
│       └── SimilarTermsWeightTest.php     # Tests for weight-based features
└── fixtures/
    └── views.view.test_similar_terms.yml  # Test view configuration
```

## Test Types

### Kernel Tests

Kernel tests bootstrap a minimal Drupal environment and test the module's integration with Views.

- **SimilarTermsTestBase**: Provides common setup for all tests including:
  - Node type creation (article)
  - Taxonomy vocabulary (tags)
  - Term reference field
  - Test taxonomy terms (PHP, JavaScript, Python, Drupal, React)
  - Test nodes with various term combinations

- **SimilarTermsArgumentTest**: Tests the Similar Terms argument handler
  - Validation with no terms (Issue #1 fix)
  - Validation with terms
  - Query includes all nodes with LEFT JOIN (Issue #2 fix)
  - Include/exclude argument node option
  - Vocabulary filtering
  - Minimum match percentage filtering
  - Single term matching with operator optimization
  - Multiple argument values (comma-separated node IDs)

- **SimilarTermsFieldTest**: Tests the Similar Terms field handler
  - Rendering with count display type
  - Rendering with percentage display type
  - Zero similarity handling
  - Handling when argument node has no terms
  - Percentage with/without suffix
  - Null value safety
  - Field configuration defaults

- **SimilarTermsWeightTest**: Tests weight-based sorting and field display
  - Term weight storage and retrieval
  - Weight-based sorting vs count-based sorting
  - Weight sum calculation accuracy
  - Weight sum field display
  - Zero-weight term handling
  - Ascending/descending weight sort order
  - Backward compatibility with count/percentage features
  - Sort configuration defaults

## Running Tests

### Run all module tests:
```bash
vendor/bin/phpunit web/modules/custom/similarterms/tests
```

### Run specific test class:
```bash
vendor/bin/phpunit web/modules/custom/similarterms/tests/src/Kernel/SimilarTermsArgumentTest.php
```

### Run with DDEV:
```bash
ddev exec vendor/bin/phpunit web/modules/custom/similarterms/tests
```

## Test Coverage

The tests cover the following scenarios:

### Issue #1: Display content when argument node has no terms
- ✓ Argument validation passes with zero terms
- ✓ View executes with zero terms
- ✓ Field displays appropriate value (0 or 0%)

### Issue #2: Display all content sorted by similarity
- ✓ Query uses LEFT JOIN to include non-matching nodes
- ✓ Results include nodes with 0% similarity
- ✓ Results are sortable by similarity count
- ✓ Can be combined with secondary sorts

### Edge Cases
- ✓ Division by zero protection
- ✓ Null value handling
- ✓ Vocabulary filtering with no terms
- ✓ Include/exclude argument node

## Test Data

### Standard Tests (SimilarTermsArgumentTest, SimilarTermsFieldTest)

Test nodes created:
- **Node 1**: PHP, Drupal (2 terms)
- **Node 2**: PHP, Drupal, JavaScript (3 terms) - 100% match with Node 1
- **Node 3**: JavaScript only (1 term) - 0% match with Node 1
- **Node 4**: Python, React (2 terms) - 0% match with Node 1
- **Node 5**: No terms - 0% match with Node 1

This data structure allows testing:
- Exact matches
- Partial matches
- No matches
- Zero terms scenarios

### Weight Tests (SimilarTermsWeightTest)

Test terms with weights:
- **PHP**: weight = 0
- **JavaScript**: weight = 1
- **Python**: weight = 5
- **Drupal**: weight = 50
- **React**: weight = 200

Test nodes created:
- **Node 1**: PHP, JavaScript, Python (3 terms, total weight = 6)
- **Node 2**: Drupal only (1 term, total weight = 50)
- **Node 3**: React only (1 term, total weight = 200)
- **Node 4**: Python, Drupal (2 terms, total weight = 55)
- **Node 5**: All terms (5 terms, total weight = 256)
- **Node 6**: No terms (0 terms, total weight = 0)

This data structure demonstrates:
- Count-based vs weight-based sorting differences
- High-weight terms outranking multiple low-weight terms
- Zero-weight term handling
- Weight sum calculations

## Extending Tests

To add new tests:

1. Extend `SimilarTermsTestBase` for new test classes
2. Use the provided test nodes and terms
3. Create additional fixtures if needed
4. Follow Drupal testing standards

## PHPUnit Configuration

The module uses the standard Drupal PHPUnit configuration. Ensure your `phpunit.xml` is properly configured for kernel tests:

```xml
<phpunit bootstrap="tests/bootstrap.php">
  <testsuites>
    <testsuite name="kernel">
      <directory>./web/modules/custom/similarterms/tests/src/Kernel</directory>
    </testsuite>
  </testsuites>
</phpunit>
```

## Continuous Integration

Tests should be run as part of CI/CD pipeline. The module includes `.gitlab-ci.yml` for GitLab CI integration.

## Additional Resources

- [Drupal PHPUnit documentation](https://www.drupal.org/docs/automated-testing/phpunit-in-drupal)
- [Views testing examples](https://git.drupalcode.org/project/drupal/-/tree/HEAD/core/modules/views/tests)
- [Kernel test documentation](https://www.drupal.org/docs/automated-testing/phpunit-in-drupal/kernel-tests)
