<?php

namespace Aliziodev\LaravelTerms\Tests\Feature;

use Aliziodev\LaravelTerms\Tests\TestCase;
use Aliziodev\LaravelTerms\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

class BasicTermTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_term()
    {
        $term = Term::create([
            'name' => 'Electronics',
            'type' => 'category',
            'slug' => 'electronics'
        ]);

        $this->assertDatabaseHas('terms', [
            'name' => 'Electronics',
            'slug' => 'electronics'
        ]);

        $retrievedTerm = Term::where('slug', 'electronics')->first();
        $this->assertEquals('Electronics', $retrievedTerm->name);
    }

    /** @test */
    public function it_can_handle_meta_attributes()
    {
        $term = Term::create([
            'name' => 'Electronics',
            'type' => 'category',
            'meta' => ['featured' => true, 'color' => 'blue']
        ]);

        $this->assertEquals(true, $term->meta['featured']);
        $this->assertEquals('blue', $term->meta['color']);
    }

    /** @test */
    public function it_can_handle_hierarchy()
    {
        $parent = Term::create([
            'name' => 'Electronics',
            'type' => 'category'
        ]);

        $child = Term::create([
            'name' => 'Phones',
            'type' => 'category',
            'parent_id' => $parent->id
        ]);

        $grandchild = Term::create([
            'name' => 'Smartphones',
            'type' => 'category',
            'parent_id' => $child->id
        ]);

        $this->assertTrue($grandchild->isDescendantOf($parent));
        $this->assertTrue($parent->isAncestorOf($grandchild));
        $this->assertEquals(2, $grandchild->depth);
    }

    /** @test */
    public function it_enforces_max_depth()
    {
        // Setup max depth di config
        config(['terms.types.category.max_depth' => 2]);
        
        // Level 1
        $electronics = Term::create([
            'name' => 'Electronics',
            'type' => 'category'
        ]);

        // Level 2
        $phones = Term::create([
            'name' => 'Phones',
            'type' => 'category',
            'parent_id' => $electronics->id
        ]);

        // Level 3 - ini seharusnya throw exception
        $this->expectException(InvalidArgumentException::class);
        
        Term::create([
            'name' => 'Android Phones',
            'type' => 'category',
            'parent_id' => $phones->id
        ]);
    }

    /** @test */
    public function it_can_handle_ordering()
    {
        $term1 = Term::create(['name' => 'First', 'type' => 'category']);
        $term2 = Term::create(['name' => 'Second', 'type' => 'category']);
        $term3 = Term::create(['name' => 'Third', 'type' => 'category']);

        $ordered = Term::ordered()->get();
        $this->assertEquals(['First', 'Second', 'Third'], $ordered->pluck('name')->toArray());
    }
} 