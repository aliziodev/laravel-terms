# Laravel Terms Package

## Version 1.0.0
### Complete Documentation

---

## Table of Contents
1. [Introduction](#introduction)
2. [Installation & Setup](#installation--setup)
3. [Configuration](#configuration)
4. [Basic Usage](#basic-usage)
5. [Advanced Usage](#advanced-usage)
6. [Available Methods](#available-methods)
   - [TermService Methods](#termservice-methods)
   - [HasTerms Trait Methods](#hasterms-trait-methods)
7. [Console Commands](#console-commands)
8. [Best Practices](#best-practices)
9. [Examples & Recipes](#examples--recipes)
10. [Troubleshooting](#troubleshooting)

---

## 1. Introduction

**Laravel Terms** is a comprehensive package for managing taxonomies, categories, tags, and hierarchical terms in Laravel applications. It provides a flexible and powerful way to organize content with features like meta data, ordering, and caching.

### Key Features:
- Hierarchical terms management
- Meta data support
- Term ordering and reordering
- Built-in caching system
- Polymorphic relationships
- Multiple term types
- Bulk operations
- Advanced querying

### Requirements:
- PHP >= 8.0
- Laravel >= 9.0
- MySQL/PostgreSQL database

---

## 2. Installation & Setup

### 1. Install via Composer:
```sh
composer require aliziodev/laravel-terms
```

### 2. Publish configuration and migrations:
```sh
php artisan terms:install
```

### 3. Run migrations:
```sh
php artisan migrate
```

### 4. Add `HasTerms` trait to your models:
```php
use Aliziodev\LaravelTerms\Traits\HasTerms;

class Post extends Model
{
    use HasTerms;
}

class Product extends Model
{
    use HasTerms;
}
```

---

## 3. Configuration

**Configuration file:** `config/terms.php`

### Key Configuration Options:

#### 1. Term Types:
```php
'types' => [
    'category' => [
        'hierarchical' => true,
        'max_depth' => 3,
    ],
    'tag' => [
        'hierarchical' => false,
    ],
]
```

#### 2. Database Tables:
```php
'table_names' => [
    'terms' => 'terms',
    'termables' => 'termables',
]
```

#### 3. Caching:
```php
'cache' => [
    'enabled' => true,
    'prefix' => 'terms',
    'ttl' => 3600, // 1 hour
]
```

#### 4. Meta Settings:
```php
'meta' => [
    'enabled' => true,
    'defaults' => [],
    'reserved_keys' => [
        'id', 'name', 'slug', 'type',
        'parent_id', 'order', 'created_at',
        'updated_at', 'deleted_at'
    ],
]
```

#### 5. Ordering:
```php
'ordering' => [
    'column' => 'order',
    'auto_order' => true,
    'start_position' => 1,
]
```

---

## 4. Basic Usage

### 1. Creating Terms:
```php
$term = Term::create([
    'name' => 'Electronics',
    'type' => 'category'
]);
```

### 2. Attaching Terms:
```php
// Single term
$post->attachTerm($term);

// Multiple terms
$post->attachTerms(['electronics', 'gadgets']);
```

### 3. Syncing Terms:
```php
$post->syncTerms(['electronics', 'gadgets']);
```

### 4. Detaching Terms:
```php
$post->detachTerm($term);
$post->detachTerms(['electronics', 'gadgets']);
```

### 5. Checking Terms:
```php
if ($post->hasTerm($term)) {
    // Has specific term
}

if ($post->hasTerms(['electronics', 'gadgets'])) {
    // Has all specified terms
}
```

---

## 5. Advanced Usage

### 1. Meta Data Management:
```php
// Set meta for term
$post->setTermMeta($term, [
    'featured' => true,
    'priority' => 'high'
]);

// Get term meta
$meta = $post->getTermMeta($term);
```

### 2. Hierarchy Management:
```php
// Get hierarchy
$hierarchy = $post->getTermsHierarchy();

// Get as tree
$tree = $post->getTermsAsTree();
```

---

## 6. Available Methods

### A. TermService Methods
#### 1. Basic CRUD:
```php
- create(array $attributes): Term
- update(Term $term, array $attributes): Term
- delete(Term $term): bool
- all(): Collection
```

#### 2. Query Methods:
```php
- getByType(string $type): Collection
- getBySlug(string $slug): ?Term
- getById(int $id): ?Term
- search(string $keyword, ?string $type): Collection
```

---

## 7. Console Commands

### 1. Installation:
```sh
php artisan terms:install
```

### 2. Clear Cache:
```sh
php artisan terms:clear-cache
```

### 3. Sync Order:
```sh
php artisan terms:sync-order
```

---

## 8. Best Practices

- Use constants for term types
- Enable caching in production
- Regular cache clearing
- Proper indexing

---

## 9. Examples & Recipes

### 1. Basic Category System:
```php
$category = Term::create([
    'name' => 'Electronics',
    'type' => 'category'
]);
$product->attachTerm($category);
```

---

## 10. Troubleshooting

### 1. Cache Issues:
- Clear cache: `php artisan terms:clear-cache`
- Verify cache configuration
- Check cache driver settings

### 2. Hierarchy Issues:
- Verify `max_depth` settings
- Check for circular references
- Validate parent-child relationships

For more detailed information, please refer to the method documentation in the source code or create an issue in the repository.

---

**End of Documentation**

