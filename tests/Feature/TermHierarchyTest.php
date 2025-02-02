<?php

namespace Aliziodev\LaravelTerms\Tests\Feature;

use Aliziodev\LaravelTerms\Tests\TestCase;
use Aliziodev\LaravelTerms\Models\Term;
use Aliziodev\LaravelTerms\Facades\Term as TermFacade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Aliziodev\LaravelTerms\Exceptions\TermHierarchyException;

class TermHierarchyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_hierarchical_terms()
    {
        $electronics = TermFacade::create([
            'name' => 'Electronics',
            'type' => 'category',
            'slug' => 'electronics'
        ]);

        $phones = TermFacade::create([
            'name' => 'Phones',
            'type' => 'category',
            'slug' => 'phones',
            'parent_id' => $electronics->id
        ]);

        $smartphones = TermFacade::create([
            'name' => 'Smartphones',
            'type' => 'category',
            'slug' => 'smartphones',
            'parent_id' => $phones->id
        ]);

        $this->assertEquals($electronics->id, $phones->parent_id);
        $this->assertEquals($phones->id, $smartphones->parent_id);
    }

    /** @test */
    public function it_can_get_ancestors()
    {
        $electronics = TermFacade::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']);
        $phones = TermFacade::create(['name' => 'Phones', 'type' => 'category', 'slug' => 'phones', 'parent_id' => $electronics->id]);
        $smartphones = TermFacade::create(['name' => 'Smartphones', 'type' => 'category', 'slug' => 'smartphones', 'parent_id' => $phones->id]);

        $ancestors = TermFacade::getAncestors($smartphones);

        $this->assertEquals(2, $ancestors->count());
        $this->assertEquals('Electronics', $ancestors->first()->name);
        $this->assertEquals('Phones', $ancestors->last()->name);
    }

    /** @test */
    public function it_can_get_descendants()
    {
        $electronics = TermFacade::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']);
        $phones = TermFacade::create(['name' => 'Phones', 'type' => 'category', 'slug' => 'phones', 'parent_id' => $electronics->id]);
        $smartphones = TermFacade::create(['name' => 'Smartphones', 'type' => 'category', 'slug' => 'smartphones', 'parent_id' => $phones->id]);
        $tablets = TermFacade::create(['name' => 'Tablets', 'type' => 'category', 'slug' => 'tablets', 'parent_id' => $electronics->id]);

        $descendants = TermFacade::getDescendants($electronics);

        $this->assertEquals(3, $descendants->count());
        $this->assertTrue($descendants->pluck('name')->contains('Phones'));
        $this->assertTrue($descendants->pluck('name')->contains('Smartphones'));
        $this->assertTrue($descendants->pluck('name')->contains('Tablets'));
    }

    /** @test */
    public function it_can_get_siblings()
    {
        $electronics = TermFacade::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']);
        $phones = TermFacade::create(['name' => 'Phones', 'type' => 'category', 'slug' => 'phones', 'parent_id' => $electronics->id]);
        $tablets = TermFacade::create(['name' => 'Tablets', 'type' => 'category', 'slug' => 'tablets', 'parent_id' => $electronics->id]);
        $laptops = TermFacade::create(['name' => 'Laptops', 'type' => 'category', 'slug' => 'laptops', 'parent_id' => $electronics->id]);

        $siblings = TermFacade::getSiblings($phones);

        $this->assertEquals(2, $siblings->count());
        $this->assertTrue($siblings->pluck('name')->contains('Tablets'));
        $this->assertTrue($siblings->pluck('name')->contains('Laptops'));
    }

    /** @test */
    public function it_can_get_root_terms()
    {
        $electronics = TermFacade::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']);
        $phones = TermFacade::create(['name' => 'Phones', 'type' => 'category', 'slug' => 'phones', 'parent_id' => $electronics->id]);
        $books = TermFacade::create(['name' => 'Books', 'type' => 'category', 'slug' => 'books']);

        $roots = TermFacade::getRoots();

        $this->assertEquals(2, $roots->count());
        $this->assertTrue($roots->pluck('name')->contains('Electronics'));
        $this->assertTrue($roots->pluck('name')->contains('Books'));
    }

    /** @test */
    public function it_can_get_tree()
    {
        $electronics = TermFacade::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']);
        $phones = TermFacade::create(['name' => 'Phones', 'type' => 'category', 'slug' => 'phones', 'parent_id' => $electronics->id]);
        $smartphones = TermFacade::create(['name' => 'Smartphones', 'type' => 'category', 'slug' => 'smartphones', 'parent_id' => $phones->id]);
        $tablets = TermFacade::create(['name' => 'Tablets', 'type' => 'category', 'slug' => 'tablets', 'parent_id' => $electronics->id]);

        $tree = TermFacade::getTree();

        $this->assertEquals(1, $tree->count());
        $this->assertTrue($tree->contains('name', 'Electronics'));
        
        $electronicsNode = $tree->first();
        $this->assertEquals(2, $electronicsNode->children->count());
        $this->assertTrue($electronicsNode->children->contains('name', 'Phones'));
        $this->assertTrue($electronicsNode->children->contains('name', 'Tablets'));
        
        $phonesNode = $electronicsNode->children->firstWhere('name', 'Phones');
        $this->assertEquals(1, $phonesNode->children->count());
        $this->assertTrue($phonesNode->children->contains('name', 'Smartphones'));
    }

    /** @test */
    public function it_can_move_terms()
    {
        $electronics = TermFacade::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']);
        $phones = TermFacade::create(['name' => 'Phones', 'type' => 'category', 'slug' => 'phones', 'parent_id' => $electronics->id]);
        $tablets = TermFacade::create(['name' => 'Tablets', 'type' => 'category', 'slug' => 'tablets']);

        TermFacade::moveTo($tablets, $phones);

        $tablets->refresh();

        $this->assertEquals($phones->id, $tablets->parent_id);
        $this->assertTrue($phones->children->contains($tablets));
    }

    /** @test */
    public function it_enforces_max_depth_on_move()
    {
        config(['terms.types.category.max_depth' => 3]);
        config(['terms.types.category.hierarchical' => true]);

        $root = TermFacade::create(['name' => 'Root', 'type' => 'category', 'slug' => 'root']);
        $child = TermFacade::create([
            'name' => 'Child',
            'type' => 'category',
            'slug' => 'child',
            'parent_id' => $root->id
        ]);
        $grandChild = TermFacade::create([
            'name' => 'Grandchild',
            'type' => 'category',
            'slug' => 'grandchild',
            'parent_id' => $child->id
        ]);
        $newTerm = TermFacade::create(['name' => 'New', 'type' => 'category', 'slug' => 'new']);

        $this->expectException(TermHierarchyException::class);
        $this->expectExceptionMessage("Maximum depth exceeded for term type category");
        
        TermFacade::moveTo($newTerm, $grandChild);
    }
} 