<?php

namespace Aliziodev\LaravelTerms\Tests\Feature;

use Aliziodev\LaravelTerms\Tests\TestCase;
use Aliziodev\LaravelTerms\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class HasTermsTest extends TestCase
{
    use RefreshDatabase;

    protected $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = $this->createProduct();
        Event::fake();
    }

    /** @test */
    public function model_can_attach_and_detach_terms()
    {
        $term = Term::create([
            'name' => 'Electronics',
            'type' => 'category',
            'slug' => 'electronics'
        ]);

        $this->model->attachTerm($term);
        $this->assertTrue($this->model->hasTerm($term));

        $this->model->detachTerm($term);
        $this->assertFalse($this->model->hasTerm($term));
    }

    /** @test */
    public function model_can_sync_terms()
    {
        $term1 = Term::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']);
        $term2 = Term::create(['name' => 'Premium', 'type' => 'tag', 'slug' => 'premium']);
        $term3 = Term::create(['name' => 'Featured', 'type' => 'tag', 'slug' => 'featured']);

        $this->model->syncTerms([$term1->id, $term2->id]);
        $this->assertTrue($this->model->hasTerms([$term1, $term2]));
        
        $this->model->syncTerms([$term2->id, $term3->id]);
        $this->assertFalse($this->model->hasTerm($term1));
        $this->assertTrue($this->model->hasTerms([$term2, $term3]));
        
    }

    /** @test */
    public function model_can_get_terms_by_type()
    {
        $category = Term::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']);
        $tag = Term::create(['name' => 'Featured', 'type' => 'tag', 'slug' => 'featured']);

        $this->model->attachTerms([$category, $tag]);

        $this->assertCount(1, $this->model->getTermsByType('category'));
        $this->assertCount(1, $this->model->getTermsByType('tag'));
        $this->assertTrue($this->model->hasTermOfType('category'));
    }

    /** @test */
    public function model_can_handle_term_meta()
    {
        $term = Term::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']);
        $this->model->attachTerm($term);

        $this->model->setTermMeta($term, ['featured' => true]);
        $this->assertEquals(['featured' => true], $this->model->getTermMeta($term));

        $this->model->updateTermsMeta([$term->id => ['priority' => 1]]);
        $this->assertEquals(['priority' => 1], $this->model->getTermMeta($term));
        
    }

    /** @test */
    public function model_can_handle_primary_terms()
    {
        $term1 = Term::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']);
        $term2 = Term::create(['name' => 'Gadgets', 'type' => 'category', 'slug' => 'gadgets']);

        $this->model->attachTerms([$term1, $term2]);
        $this->model->setPrimaryTerm($term1, 'category');

        $primaryTerm = $this->model->getPrimaryTerm('category');
        $this->assertEquals($term1->id, $primaryTerm->id);
    }

    /** @test */
    public function model_can_handle_term_ordering()
    {
        $term1 = Term::create(['name' => 'First', 'type' => 'category', 'slug' => 'first']);
        $term2 = Term::create(['name' => 'Second', 'type' => 'category', 'slug' => 'second']);
        
        $this->model->attachTerms([$term1, $term2]);
        
        // Test reordering
        Term::where('id', $term2->id)->update(['order' => 1]);
        Term::where('id', $term1->id)->update(['order' => 2]);
        
        $orderedTerms = $this->model->getOrderedTerms();
        
        $this->assertEquals(['Second', 'First'], $orderedTerms->pluck('name')->toArray());
    }

    /** @test */
    public function model_can_move_terms()
    {
        $term1 = Term::create(['name' => 'First', 'type' => 'category', 'slug' => 'first']);
        $term2 = Term::create(['name' => 'Second', 'type' => 'category', 'slug' => 'second']);
        $term3 = Term::create(['name' => 'Third', 'type' => 'category', 'slug' => 'third']);

        $this->model->attachTerms([$term1, $term2, $term3]);
        
        // Update order directly
        Term::where('id', $term2->id)->update(['order' => 1]);
        Term::where('id', $term1->id)->update(['order' => 2]);
        Term::where('id', $term3->id)->update(['order' => 3]);
        
        $orderedTerms = $this->model->getOrderedTerms();
        
        $this->assertEquals(['Second', 'First', 'Third'], $orderedTerms->pluck('name')->toArray());
    }

    /** @test */
    public function model_can_get_terms_count()
    {
        $category = Term::create(['name' => 'Electronics', 'type' => 'category', 'slug' => 'electronics']);
        $tag1 = Term::create(['name' => 'Featured', 'type' => 'tag', 'slug' => 'featured']);
        $tag2 = Term::create(['name' => 'Premium', 'type' => 'tag', 'slug' => 'premium']);

        $this->model->attachTerms([$category, $tag1, $tag2]);

        $this->assertEquals(3, $this->model->countTerms());
        $this->assertEquals(1, $this->model->countTerms('category'));
        $this->assertEquals(2, $this->model->countTerms('tag'));

        $counts = $this->model->getTermsCount();
        $this->assertEquals(['category' => 1, 'tag' => 2], $counts);
    }
} 