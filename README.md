# LARAVEL TERMS PACKAGE

## Version 1.1.0

Laravel Terms is a powerful and flexible package for managing taxonomies, categories, tags, and hierarchical terms in Laravel applications. It provides a robust solution for organizing content with features like metadata support, ordering capabilities, and efficient caching mechanisms.

This package is ideal for:

-   E-commerce category management
-   Blog taxonomies
-   Content organization
-   Product attributes
-   Dynamic navigation
-   Any hierarchical data structure

## KEY FEATURES

-   Hierarchical terms (parent-child)
-   Metadata support (JSON)
-   Term ordering
-   Caching system
-   Polymorphic relationships
-   Multiple term types
-   Bulk operations
-   Advanced querying
-   Tree structure
-   Flat tree structure

## INSTALLATION

```bash
# Install via composer
composer require aliziodev/laravel-terms

# Publish config & migrations
php artisan terms:install

# Run migrations
php artisan migrate
```

## CONFIGURATION

```php
// config/terms.php
return [
    'types' => [
        'category',
        'tag',
        'size',
        'color',
        'unit',
        'type',
        'brand',
        'model',
        'variant',
    ],
];
```

## BASIC USAGE

### 1. Model Setup

```php
use Aliziodev\LaravelTerms\Traits\HasTerms;

class Post extends Model
{
    use HasTerms;
}
```

### 2. Basic Operations

```php
// Create term
$term = Term::create([
    'name' => 'Electronics',
    'type' => 'category'
]);

// Attach terms
$post->attachTerms(['electronics', 'gadgets']);

// Sync terms
$post->syncTerms(['electronics', 'gadgets']);

// Detach terms
$post->detachTerms(['electronics']);

// Get terms by type
$categories = $post->getTermsByType('category');

// Get terms grouped
$terms = $post->getTermsByGroups(['category', 'tag']);
```

### 3. Tree Operations

```php
// Get tree structure
Term::tree();
Term::treeFlat();

// Get ancestors & descendants
$term->getAncestors();
$term->getDescendants();

// Get computed attributes
$term->path;      // 'electronics/phones/iphone'
$term->depth;     // 2
$term->is_leaf;   // true/false
$term->is_root;   // true/false
```

### 4. Ordering

```php
// Move term
$term->moveToStart();
$term->moveToEnd();
$term->moveBefore($otherTerm);
$term->moveAfter($otherTerm);
$term->moveToOrder(5);
```

### 5. Meta Data

```php
// Set meta
$term->setMeta(['icon' => 'phone']);

// Get meta
$term->getMeta('icon');

// Update meta
$term->updateMeta(['icon' => 'new-phone']);

// Remove meta
$term->removeMeta('icon');

// Update meta for type
Term::updateMetaForType('category', ['visible' => true]);
```

## API ENDPOINTS

```http
# List & Filter
GET /terms
GET /terms?type=category
GET /terms?parent_id=1
GET /terms?root=true
GET /terms?leaf=true
GET /terms?search=keyword
GET /terms?tree=true
GET /terms?flat_tree=true

# CRUD
POST /terms
GET /terms/{id}
PUT /terms/{id}
DELETE /terms/{id}

# Tree Operations
GET /terms/{id}?tree=true
GET /terms/{id}?ancestors=true
GET /terms/{id}?descendants=true

# Move Operations
POST /terms/{id}/move
{
    "parent_id": 1,
    "position": "before|after|start|end",
    "target_id": 2,
    "order": 5
}

# Statistics
GET /terms/stats

# Search
GET /terms/search?keyword=phone&type=category
```

## QUERY SCOPES

```php
Term::search('keyword');    // Search in name, slug, description
Term::type('category');     // Filter by type
Term::root();              // Get root terms
Term::leaf();              // Get leaf terms
```

## BEST PRACTICES

1. Validate inputs
2. Use transactions for complex operations
3. Cache term instances
4. Use eager loading for relationships
5. Limit hierarchy depth
6. Validate term types
7. Handle circular references
8. Sanitize metadata
9. Use bulk operations
10. Maintain order consistency

## TROUBLESHOOTING

1. Reset cache if data is inconsistent
2. Check for circular references in hierarchy
3. Validate term types
4. Check order sequence
5. Monitor query performance
6. Use eager loading
7. Optimize metadata queries
8. Handle soft deletes
9. Maintain indexes
10. Monitor cache usage

For more detailed information, please check the source code or create an issue in the repository.
