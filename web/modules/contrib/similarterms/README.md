# Similar By Terms

This Drupal module attempts to provide context for content items by displaying a
view block with links to other similar content. Similarity is based on the
taxonomy terms assigned to content. Views are available based on similarity
within each of the defined vocabularies for a site as well as similarity within
all vocabularies.

For a full description of the module, visit the
[project page](https://drupal.org/project/similarterms)

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://drupal.org/project/issues/similarterms)

## Requirements

This module requires no modules outside of Drupal core.

Optionally, the
[Taxonomy Entity Index](https://www.drupal.org/project/taxonomy_entity_index)
module can be installed to enable similarity for entity types other than
content (nodes). See "Support for Other Entity Types" below.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

Configuration is accomplished per view:

1. Navigate to Administration » Structure » Views and create a view.
1. Add a contextual filter "Similar by terms: Nid".
1. Add a "Similar by terms: Similarity" sort criteria.
1. (Optional) Add the "Similar by terms: Similarity" field to output. The
similarity field can be configured to output the count of matching
terms, a percentage, or the sum of matching term weights.
1. Save the View and place the block on a specific content type.

### Contextual Filter Options

When configuring the "Similar by terms: Nid" contextual filter, you have several options:

**Vocabularies**
- Limit similarity calculation to specific vocabularies
- Leave unselected to compare across all vocabularies
- Useful when only certain term types should be considered

**Include argument node(s) in results**
- Check to include the source node in the results
- Uncheck to exclude it (default)
- Useful for debugging or specific use cases

**Minimum similarity match**
- Filter results by minimum match percentage
- Options: No minimum, 25%, 50%, 75%, 100% (exact match)
- **100% (Exact match only)**: Shows only nodes with all the same terms
- Use case example: Show foods with exactly the same dietary properties

### Sort Criteria Options

When configuring the "Similar by terms: Similarity" sort criteria, sorting is based on the count of matching terms by default. This determines the order of similar content in your results.

### Field Display Options

When adding the "Similar by terms: Similarity" field to your view, you can choose how to display the similarity value:

**Display type**
- **Show count of common terms**: Displays the raw number of matching terms (e.g., "3" for three matching terms)
- **Show as percentage**: Displays the match as a percentage of the source node's total terms (e.g., "75%" means 3 of 4 terms match)
- **Show sum of matching term weights**: Displays the total weight of all matching terms (useful when term importance varies)

**Append % when showing percentage**
- When enabled, adds the "%" symbol after percentage values
- Only applies when "Show as percentage" is selected

## Support for Other Entity Types

Out of the box, similarity is only available for content (nodes), because it
is calculated from Drupal core's `taxonomy_index` table, which only indexes
published nodes.

When the
[Taxonomy Entity Index](https://www.drupal.org/project/taxonomy_entity_index)
module is enabled, similarity is calculated from that module's index instead,
which covers taxonomy term references on any content entity type. The Similar
By Terms contextual filter, sort criteria, and field then become available on
every content entity type (media, users, taxonomy terms, custom entities,
etc.), not just content.

### Setup

1. Install and enable the Taxonomy Entity Index module.
2. If you have pre-existing content, re-index it so its term references are
included (see the Taxonomy Entity Index documentation).
3. Create a view of the desired entity type (for example, Media).
4. Add the contextual filter "Similar by terms: [Entity type] ID". This is the
equivalent of the "Similar by terms: Nid" filter for nodes and takes the
entity ID as its argument.
5. Add the "Similar by terms: Similarity" sort criteria and (optionally) the
similarity field, exactly as described above for nodes.

### Notes

- With Taxonomy Entity Index enabled, the handlers for node views are also
provided through its index, and the contextual filter is labeled
"Similar by terms: Content ID" instead of "Similar by terms: Nid". Existing
views keep working without modification.
- All contextual filter options (vocabulary limiting, including the argument
entity in results, minimum similarity match) and all field display options
work the same for every entity type.
- Similarity is only ever calculated between entities of the same type as the
view's base entity type.

## Weight-Based Similarity

By default, similarity is calculated by counting matching terms. However, when some terms are more important than others, you can use Drupal's built-in term weight field to prioritize certain terms in your similarity calculations.

### Understanding Term Weights

In Drupal, taxonomy terms have a "weight" field (accessible when editing a term) that determines their display order. This module leverages those same weights to calculate weighted similarity scores.

**How it works:**
- Each taxonomy term has a weight value (default: 0)
- Negative weights (e.g., -10) indicate less importance
- Positive weights (e.g., +10) indicate greater importance
- The similarity score is the sum of weights for all matching terms
- Only the weights of matching terms between nodes are counted

### Setting Term Weights

1. Navigate to Structure » Taxonomy » [Your Vocabulary]
2. Click "Edit" on any term
3. Set the "Weight" field to control importance
4. Higher weights = more important terms
5. Save the term

**Weight recommendations:**
- Critical terms (brands, key features): +10 to +20
- Important terms (categories, types): +5 to +10
- Standard terms (colors, sizes): 0 to +5
- Minor attributes: -5 to 0

### Use Cases for Weight-Based Similarity

**E-commerce Product Recommendations**

When recommending similar products, brand and price range are often more important than color or size.

Example taxonomy structure:
- **Brand terms** (weight: +15): Nike, Adidas, Puma
- **Price Range terms** (weight: +10): Budget, Mid-range, Premium
- **Category terms** (weight: +5): Running, Training, Casual
- **Color terms** (weight: 0): Red, Blue, Black
- **Size terms** (weight: 0): Small, Medium, Large

Product A: Nike (+15), Premium (+10), Running (+5), Red (0), Large (0) = 30 points
Product B: Nike (+15), Premium (+10), Training (+5), Blue (0) = 30 points (3 matches)
Product C: Nike (+15), Budget (+10), Running (+5), Red (0) = 30 points (4 matches)
Product D: Adidas (+15), Premium (+10), Running (+5), Red (0) = 30 points (4 matches)

Using count-based sorting: Products C and D would rank highest (4 matching terms)
Using weight-based sorting: Product B ranks highest (shares important brand + price terms)

This ensures customers see products in the same brand and price range, rather than just products with many insignificant attribute matches.

**Content Classification**

For articles or blog posts, subject matter tags may be more important than format or difficulty level tags.

Example: Technical tutorials where framework/language terms have higher weights than difficulty level terms ensures similar content suggestions focus on the same technology stack.

### Configuration Example: E-commerce Setup

1. **Create your view** with the similar terms contextual filter
2. **Set term weights** in your Product Attributes vocabulary:
   - Brands: weight +15
   - Price ranges: weight +10
   - Categories: weight +5
   - Colors/Sizes: weight 0
3. **Configure the similarity field** (optional):
   - Display type: "Show sum of matching term weights"
   - This shows the total weight score (e.g., "35" for matching brand + price + category)
4. **Add similarity sort**:
   - Uses weighted sum automatically when weights are present
   - Orders products by importance of matching attributes
5. **Result**: Products with matching high-weight terms (brands, prices) appear first, even if products with more low-weight matches (colors, sizes) exist

### Comparison: Count vs Weight Behavior

**Scenario:** Recommending similar products to a "Nike Premium Running Shoe"

Product terms:
- Source: Nike (+15), Premium (+10), Running (+5), Red (0), Large (0)

Potential matches:
- Product A: Nike (+15), Premium (+10), Training (+5), Blue (0) = 3 matches, 30 weight
- Product B: Nike (+15), Budget (+10), Running (+5), Red (0), Large (0) = 4 matches, 30 weight
- Product C: Adidas (+15), Premium (+10), Running (+5), Red (0), Large (0) = 4 matches, 30 weight

**Count-based sorting (default):**
1. Product B (4 matches) - different price range but matches color/size
2. Product C (4 matches) - different brand but matches price/category/attributes
3. Product A (3 matches) - same brand and price but different category

**Weight-based sorting:**
1. Product A (weight: 30, brand + price match)
2. Product B (weight: 30, brand + category match)
3. Product C (weight: 30, price + category match)

Note: When weights are equal, you can add a secondary sort criterion (like title or random) to break ties.

### Backward Compatibility

Weight-based sorting is fully backward compatible:
- If all terms have the default weight (0), weighted sum produces the same results as count-based sorting
- Existing views continue to work without modification
- You can migrate gradually by setting weights only on critical terms

### Usage Examples

**Example 1: Related Articles (Any Similarity)**
- Sort: Similarity (DESC)
- Minimum match: No minimum
- Result: Shows most similar articles first, fills list with any articles

**Example 2: Exact Match Products**
- Sort: Similarity (DESC)
- Minimum match: 100% (Exact match only)
- Result: Shows only products with identical term combinations
- Use case: Food catalogue showing items with same carb/sugar type

**Example 3: Similar with Threshold**
- Sort: Similarity (DESC), then Random
- Minimum match: 50% or more
- Result: Shows reasonably similar content, varied when similarity is equal

**Example 4: Weight-Based E-commerce Recommendations**
- Context: Product catalog with brand terms (weight: +15), price range terms (weight: +10), color terms (weight: 0)
- Sort: Similarity (DESC)
- Field: Similarity - "Show sum of matching term weights"
- Result: Products matching on important attributes (brand, price) rank higher than products with many minor attribute matches
- Use case: Ensure "Nike Premium Running Shoes" recommendations prioritize other Nike Premium products over budget Nike shoes in different colors

## Maintainers

- Robert Middleswarth - [rmiddle](https://www.drupal.org/u/rmiddle)
- Mustakimul Islam - [takim](https://www.drupal.org/u/takim)
- Shelane French - [shelane](https://www.drupal.org/u/shelane)
