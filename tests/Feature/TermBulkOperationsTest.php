<?php

namespace Aliziodev\LaravelTerms\Tests\Feature;

use Aliziodev\LaravelTerms\Tests\TestCase;
use Aliziodev\LaravelTerms\Models\Term;
use Aliziodev\LaravelTerms\Facades\Term as TermFacade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class TermBulkOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Cache::flush();
    }

    /** @test */
    public function it_can_create_many_terms()
    {
        $terms = [
            [
                'name' => 'Electronics',
                'type' => 'category',
                'slug' => 'electronics'
            ],
            [
                'name' => 'Clothing',
                'type' => 'category',
                'slug' => 'clothing'
            ],
            [
                'name' => 'Books',
                'type' => 'category',
                'slug' => 'books'
            ]
        ];

        $createdTerms = TermFacade::createMany($terms);
        
        $this->assertEquals(3, Term::count());
        $this->assertEquals(3, $createdTerms->count());
        $this->assertInstanceOf(Term::class, $createdTerms->first());

    }

    /** @test */
    public function it_can_update_many_terms()
    {
        // Create initial terms
        $terms = collect([
            Term::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']),
            Term::create(['name' => 'Clothing', 'type' => 'category', 'slug' => 'clothing']),
            Term::create(['name' => 'Books', 'type' => 'category', 'slug' => 'books'])
        ]);

        // Prepare update data
        $updates = $terms->map(function($term) {
            return [
                'id' => $term->id,
                'name' => $term->name . ' Updated',
                'type' => 'tag',
                'slug' => $term->slug . '-updated'
            ];
        })->all();

        // Update terms using facade
        TermFacade::updateMany($updates);

        // Assert updates were successful
        $this->assertEquals(3, Term::where('type', 'tag')->count());
        $this->assertTrue(Term::where('slug', 'electronics-updated')->exists());
        $this->assertTrue(Term::where('slug', 'clothing-updated')->exists());
        $this->assertTrue(Term::where('slug', 'books-updated')->exists());

    }

    /** @test */
    public function it_can_delete_many_terms()
    {
        $terms = collect([
            Term::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']),
            Term::create(['name' => 'Phones', 'type' => 'category', 'slug' => 'phones']),
            Term::create(['name' => 'Laptops', 'type' => 'category', 'slug' => 'laptops'])
        ]);

        // Delete terms using facade
        TermFacade::deleteMany($terms->pluck('id')->toArray());

        // Assert terms are soft deleted
        $this->assertEquals(0, Term::count());
        $this->assertEquals(3, Term::withTrashed()->count());

    }

    /** @test */
    public function it_can_restore_many_terms()
    {
        $terms = collect([
            Term::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']),
            Term::create(['name' => 'Phones', 'type' => 'category', 'slug' => 'phones']),
            Term::create(['name' => 'Laptops', 'type' => 'category', 'slug' => 'laptops'])
        ]);

        // Soft delete terms first
        TermFacade::deleteMany($terms->pluck('id')->toArray());
        $this->assertEquals(0, Term::count());

        // Restore terms using facade
        TermFacade::restoreMany($terms->pluck('id')->toArray());

        // Assert terms are restored
        $this->assertEquals(3, Term::count());
        $this->assertEquals(0, Term::onlyTrashed()->count());

    }

    /** @test */
    public function it_can_force_delete_many_terms()
    {
        $terms = collect([
            Term::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']),
            Term::create(['name' => 'Phones', 'type' => 'category', 'slug' => 'phones']),
            Term::create(['name' => 'Laptops', 'type' => 'category', 'slug' => 'laptops'])
        ]);

        // Soft delete terms first
        TermFacade::deleteMany($terms->pluck('id')->toArray());
        $this->assertEquals(3, Term::withTrashed()->count());

        // Force delete terms using facade
        TermFacade::forceDeleteMany($terms->pluck('id')->toArray());

        // Assert terms are permanently deleted
        $this->assertEquals(0, Term::withTrashed()->count());

    }

    /** @test */
    public function it_can_attach_many_terms()
    {
        $terms = collect([
            Term::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']),
            Term::create(['name' => 'Phones', 'type' => 'category', 'slug' => 'phones']),
            Term::create(['name' => 'Laptops', 'type' => 'category', 'slug' => 'laptops'])
        ]);

        $product = $this->createProduct();
        $product->attachTerms($terms);

        $this->assertEquals(3, $product->terms()->count());

    }

    /** @test */
    public function it_can_detach_many_terms()
    {
        $terms = collect([
            Term::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']),
            Term::create(['name' => 'Phones', 'type' => 'category', 'slug' => 'phones']),
            Term::create(['name' => 'Laptops', 'type' => 'category', 'slug' => 'laptops'])
        ]);

        $product = $this->createProduct();
        $product->attachTerms($terms);
        $product->detachTerms($terms->take(2));

        $this->assertEquals(1, $product->terms()->count());
        $this->assertEquals('Laptops', $product->terms()->first()->name);

    }

    /** @test */
    public function it_can_sync_many_terms()
    {
        $terms = collect([
            Term::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']),
            Term::create(['name' => 'Phones', 'type' => 'category', 'slug' => 'phones']),
            Term::create(['name' => 'Laptops', 'type' => 'category', 'slug' => 'laptops'])
        ]);

        $product = $this->createProduct();
        $product->attachTerms($terms->take(2));
        
        $product->syncTerms([$terms[1], $terms[2]]);

        $this->assertEquals(2, $product->terms()->count());
        $this->assertFalse($product->hasTerm($terms[0]));
        $this->assertTrue($product->hasTerms([$terms[1], $terms[2]]));

    }

}