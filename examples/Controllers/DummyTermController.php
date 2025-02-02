<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Aliziodev\LaravelTerms\Facades\Term as TermFacade;

class TestTermController extends Controller
{
    /**
     * Contoh data dummy untuk kategori produk
     */
    public function createDummyCategories(): JsonResponse
    {
        // Buat parent categories
        $electronics = TermFacade::create([
            'name' => 'Electronics',
            'type' => 'category',
            'slug' => 'electronics',
            'meta' => ['icon' => 'laptop']
        ]);

        $fashion = TermFacade::create([
            'name' => 'Fashion',
            'type' => 'category',
            'slug' => 'fashion',
            'meta' => ['icon' => 'shirt']
        ]);

        // Buat child categories untuk Electronics
        $phones = TermFacade::create([
            'name' => 'Phones',
            'type' => 'category',
            'slug' => 'phones',
            'parent_id' => $electronics->id,
            'meta' => ['icon' => 'phone']
        ]);

        $laptops = TermFacade::create([
            'name' => 'Laptops',
            'type' => 'category',
            'slug' => 'laptops',
            'parent_id' => $electronics->id,
            'meta' => ['icon' => 'computer']
        ]);

        // Buat child categories untuk Fashion
        $mens = TermFacade::create([
            'name' => "Men's Wear",
            'type' => 'category',
            'slug' => 'mens-wear',
            'parent_id' => $fashion->id,
            'meta' => ['icon' => 'man']
        ]);

        $womens = TermFacade::create([
            'name' => "Women's Wear",
            'type' => 'category',
            'slug' => 'womens-wear',
            'parent_id' => $fashion->id,
            'meta' => ['icon' => 'woman']
        ]);

        return response()->json([
            'message' => 'Dummy categories created',
            'categories' => TermFacade::getTree('category')
        ]);
    }

    /**
     * Contoh data dummy untuk tag produk
     */
    public function createDummyTags(): JsonResponse
    {
        // Buat tags untuk Electronics
        $tags = TermFacade::createMany([
            [
                'name' => 'New Arrival',
                'type' => 'tag',
                'slug' => 'new-arrival',
                'meta' => ['color' => 'green']
            ],
            [
                'name' => 'Best Seller',
                'type' => 'tag',
                'slug' => 'best-seller',
                'meta' => ['color' => 'blue']
            ],
            [
                'name' => 'Sale',
                'type' => 'tag',
                'slug' => 'sale',
                'meta' => ['color' => 'red']
            ],
            [
                'name' => 'Limited Edition',
                'type' => 'tag',
                'slug' => 'limited-edition',
                'meta' => ['color' => 'purple']
            ]
        ]);

        return response()->json([
            'message' => 'Dummy tags created',
            'tags' => $tags
        ]);
    }

    /**
     * Contoh penggunaan term untuk produk dummy
     */
    public function createDummyProducts(): JsonResponse
    {
        // Asumsikan kita memiliki model Product yang menggunakan HasTerms
        $product = new \App\Models\Product(); // Ganti dengan model yang sebenarnya
        $product->name = 'iPhone 15 Pro';
        $product->save();

        // Attach terms
        $phones = TermFacade::getBySlug('phones');
        $newArrival = TermFacade::getBySlug('new-arrival');
        $limitedEdition = TermFacade::getBySlug('limited-edition');

        $product->attachTerms([$phones->id, $newArrival->id, $limitedEdition->id]);

        return response()->json([
            'message' => 'Dummy product created with terms',
            'product' => $product->load('terms')
        ]);
    }

    /**
     * Contoh penggunaan meta untuk terms
     */
    public function createDummyMeta(): JsonResponse
    {
        // Update meta untuk type category
        TermFacade::updateMetaForType('category', [
            'show_in_menu' => true,
            'menu_order' => 1,
            'icon_type' => 'font-awesome'
        ]);

        // Update meta untuk type tag
        TermFacade::updateMetaForType('tag', [
            'show_in_filters' => true,
            'filter_type' => 'checkbox',
            'color_scheme' => 'pastel'
        ]);

        return response()->json([
            'message' => 'Dummy meta created',
            'category_meta' => TermFacade::getMetaStats()
        ]);
    }

    /**
     * Contoh penggunaan reorder terms
     */
    public function reorderDummyTerms(): JsonResponse
    {
        $electronics = TermFacade::getBySlug('electronics');
        $children = TermFacade::getChildren($electronics);

        // Reorder children
        TermFacade::reorder($children->pluck('id')->reverse()->toArray());

        return response()->json([
            'message' => 'Terms reordered',
            'new_order' => TermFacade::getChildren($electronics)
        ]);
    }

    /**
     * Contoh penggunaan parent dan getTree
     */
    public function createDummyParentChild(): JsonResponse
    {
        // Buat parent categories
        $fashion = TermFacade::create([
            'name' => 'Fashion',
            'type' => 'category',
            'slug' => 'fashion',
            'meta' => ['icon' => 'shirt']
        ]);

        // Level 1 children
        $mens = TermFacade::create([
            'name' => "Men's Fashion",
            'type' => 'category',
            'slug' => 'mens-fashion',
            'parent_id' => $fashion->id,
            'meta' => ['icon' => 'man']
        ]);

        $womens = TermFacade::create([
            'name' => "Women's Fashion",
            'type' => 'category',
            'slug' => 'womens-fashion',
            'parent_id' => $fashion->id,
            'meta' => ['icon' => 'woman']
        ]);

        // Level 2 children untuk Men's Fashion
        $mensClothing = TermFacade::create([
            'name' => "Men's Clothing",
            'type' => 'category',
            'slug' => 'mens-clothing',
            'parent_id' => $mens->id,
            'meta' => ['icon' => 'tshirt']
        ]);

        $mensShoes = TermFacade::create([
            'name' => "Men's Shoes",
            'type' => 'category',
            'slug' => 'mens-shoes',
            'parent_id' => $mens->id,
            'meta' => ['icon' => 'shoe']
        ]);

        // Level 2 children untuk Women's Fashion
        $womensClothing = TermFacade::create([
            'name' => "Women's Clothing",
            'type' => 'category',
            'slug' => 'womens-clothing',
            'parent_id' => $womens->id,
            'meta' => ['icon' => 'dress']
        ]);

        $womensShoes = TermFacade::create([
            'name' => "Women's Shoes",
            'type' => 'category',
            'slug' => 'womens-shoes',
            'parent_id' => $womens->id,
            'meta' => ['icon' => 'high-heel']
        ]);

        // Level 3 children untuk Men's Clothing
        TermFacade::createMany([
            [
                'name' => 'T-Shirts',
                'type' => 'category',
                'slug' => 'mens-tshirts',
                'parent_id' => $mensClothing->id,
                'meta' => ['icon' => 'tshirt']
            ],
            [
                'name' => 'Shirts',
                'type' => 'category',
                'slug' => 'mens-shirts',
                'parent_id' => $mensClothing->id,
                'meta' => ['icon' => 'shirt']
            ],
            [
                'name' => 'Pants',
                'type' => 'category',
                'slug' => 'mens-pants',
                'parent_id' => $mensClothing->id,
                'meta' => ['icon' => 'pants']
            ]
        ]);

        // Get different views of the hierarchy
        $tree = TermFacade::getTree('category');
        $flat = TermFacade::getFlat('category');
        $fashionChildren = TermFacade::getChildren($fashion);
        $mensParent = TermFacade::getParent($mens);
        $mensAncestors = TermFacade::getAncestors($mensClothing);
        $mensDescendants = TermFacade::getDescendants($mens);
        $mensSiblings = TermFacade::getSiblings($mens);

        return response()->json([
            'message' => 'Dummy hierarchy created',
            'views' => [
                'tree' => $tree,
                'flat' => $flat,
                'children' => $fashionChildren,
                'parent' => $mensParent,
                'ancestors' => $mensAncestors,
                'descendants' => $mensDescendants,
                'siblings' => $mensSiblings
            ]
        ]);
    }

    /**
     * Contoh penggunaan getTree dengan berbagai tipe
     */
    public function getDummyTrees(): JsonResponse
    {
        // Get trees untuk berbagai tipe
        $categoryTree = TermFacade::getTree('category');
        $tagTree = TermFacade::getTree('tag');
        $allTree = TermFacade::getTree(); // Semua tipe

        // Get flat structure
        $categoryFlat = TermFacade::getFlat('category');

        // Get roots only
        $roots = TermFacade::getRoots('category');

        return response()->json([
            'message' => 'Term trees retrieved',
            'trees' => [
                'category_tree' => $categoryTree,
                'tag_tree' => $tagTree,
                'all_tree' => $allTree,
                'category_flat' => $categoryFlat,
                'category_roots' => $roots
            ]
        ]);
    }
}
