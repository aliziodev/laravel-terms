<?php

namespace Aliziodev\LaravelTerms\Tests\Unit;

use Aliziodev\LaravelTerms\Tests\TestCase;
use Aliziodev\LaravelTerms\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TermTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_term()
    {
        $term = Term::create([
            'name' => 'Electronics',
            'type' => 'category'
        ]);

        $this->assertDatabaseHas('terms', [
            'name' => 'Electronics',
            'type' => 'category',
            'slug' => 'electronics'
        ]);
    }

    /** @test */
    public function it_validates_term_type()
    {
        $this->expectException(\InvalidArgumentException::class);

        Term::create([
            'name' => 'Invalid',
            'type' => 'invalid_type'
        ]);
    }

    /** @test */
    public function it_enforces_max_depth()
    {
        $electronics = Term::create([
            'name' => 'Electronics',
            'type' => 'category'
        ]);

        $phones = Term::create([
            'name' => 'Phones',
            'type' => 'category',
            'parent_id' => $electronics->id
        ]);

        $smartphones = Term::create([
            'name' => 'Smartphones',
            'type' => 'category',
            'parent_id' => $phones->id
        ]);

        $this->expectException(\InvalidArgumentException::class);

        Term::create([
            'name' => 'Too Deep',
            'type' => 'category',
            'parent_id' => $smartphones->id
        ]);
    }

    /** @test */
    public function it_prevents_circular_references()
    {
        $electronics = Term::create([
            'name' => 'Electronics',
            'type' => 'category'
        ]);

        $phones = Term::create([
            'name' => 'Phones',
            'type' => 'category',
            'parent_id' => $electronics->id
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Circular reference detected');

        // Trigger circular reference check
        $electronics->parent_id = $phones->id;
        $electronics->save();
    }

    /** @test */
    public function it_can_handle_meta_data()
    {
        $term = Term::create([
            'name' => 'Electronics',
            'type' => 'category',
            'meta' => ['featured' => true]
        ]);

        $this->assertTrue($term->meta['featured']);

        // Hindari menggunakan 'order' karena itu adalah reserved key
        $term->update(['meta' => ['featured' => false, 'priority' => 1]]);
        $term->refresh();

        $this->assertFalse($term->meta['featured']);
        $this->assertEquals(1, $term->meta['priority']);
    }

    /** @test */
    public function it_can_search_terms()
    {
        Term::create(['name' => 'Electronics', 'type' => 'category']);
        Term::create(['name' => 'Books', 'type' => 'category']);
        Term::create(['name' => 'Electronic Games', 'type' => 'category']);

        $results = Term::search('electron')->get();

        $this->assertEquals(2, $results->count());
        $this->assertTrue($results->pluck('name')->contains('Electronics'));
        $this->assertTrue($results->pluck('name')->contains('Electronic Games'));
    }

    /** @test */
    public function it_can_filter_by_type()
    {
        Term::create(['name' => 'Electronics', 'type' => 'category']);
        Term::create(['name' => 'Featured', 'type' => 'tag']);
        Term::create(['name' => 'Books', 'type' => 'category']);

        $categories = Term::ofType('category')->get();
        $tags = Term::ofType('tag')->get();

        $this->assertEquals(2, $categories->count());
        $this->assertEquals(1, $tags->count());
        $this->assertEquals('Featured', $tags->first()->name);
    }

    /** @test */
    public function it_auto_generates_unique_slugs()
    {
        // Bersihkan tabel terms dulu
        Term::query()->forceDelete();
        
        $term1 = Term::create(['name' => 'Electronics', 'type' => 'category']);
        $term2 = Term::create(['name' => 'Electronics', 'type' => 'category']);
        $term3 = Term::create(['name' => 'Electronics', 'type' => 'category']);

        $this->assertEquals('electronics', $term1->slug);
        $this->assertEquals('electronics-1', $term2->slug);
        $this->assertEquals('electronics-2', $term3->slug);
    }

    /** @test */
    public function it_can_soft_delete()
    {
        $term = Term::create(['name' => 'Electronics', 'type' => 'category']);
        $term->delete();

        $this->assertSoftDeleted('terms', ['id' => $term->id]);
        $this->assertEquals(0, Term::count());
        $this->assertEquals(1, Term::withTrashed()->count());
    }
}