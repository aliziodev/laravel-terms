<?php

namespace Aliziodev\LaravelTerms\Tests\Feature;

use Aliziodev\LaravelTerms\Tests\TestCase;
use Aliziodev\LaravelTerms\Models\Term;
use Aliziodev\LaravelTerms\Facades\Term as TermFacade;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TermMetaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_set_and_get_meta()
    {
        $term = TermFacade::create([
            'name' => 'Electronics',
            'type' => 'category',
            'slug' => 'electronics',
            'meta' => [
                'featured' => true,
                'color' => 'blue'
            ]
        ]);

        $this->assertTrue($term->getMeta('featured'));
        $this->assertEquals('blue', $term->getMeta('color'));
    }

    /** @test */
    public function it_can_check_meta_existence()
    {
        $term = TermFacade::create([
            'name' => 'Electronics',
            'type' => 'category',
            'slug' => 'electronics',
            'meta' => ['featured' => true]
        ]);

        $this->assertTrue($term->hasMeta('featured'));
        $this->assertFalse($term->hasMeta('color'));
    }

    /** @test */
    public function it_can_get_all_meta()
    {
        $meta = [
            'featured' => true,
            'color' => 'blue'
        ];

        $term = TermFacade::create([
            'name' => 'Electronics',
            'type' => 'category',
            'slug' => 'electronics',
            'meta' => $meta
        ]);

        $this->assertEquals($meta, $term->getAllMeta());
    }

    /** @test */
    public function it_can_set_meta()
    {
        $term = TermFacade::create([
            'name' => 'Electronics',
            'type' => 'category',
            'slug' => 'electronics'
        ]);

        $term->setMeta('featured', true);
        $this->assertTrue($term->getMeta('featured'));

        $term->setMeta('color', 'blue');
        $this->assertEquals('blue', $term->getMeta('color'));
    }

    /** @test */
    public function it_can_unset_meta()
    {
        $term = TermFacade::create([
            'name' => 'Electronics',
            'type' => 'category',
            'slug' => 'electronics',
            'meta' => [
                'featured' => true,
                'color' => 'blue'
            ]
        ]);

        $term->unsetMeta('color');
        $this->assertFalse($term->hasMeta('color'));
        $this->assertTrue($term->hasMeta('featured'));
    }

    /** @test */
    public function it_can_get_meta_with_dot_notation()
    {
        $term = TermFacade::create([
            'name' => 'Electronics',
            'type' => 'category',
            'slug' => 'electronics',
            'meta' => [
                'settings' => [
                    'display' => 'grid',
                    'columns' => 3
                ]
            ]
        ]);

        $this->assertEquals('grid', $term->getMeta('settings.display'));
        $this->assertEquals(3, $term->getMeta('settings.columns'));
    }

    /** @test */
    public function it_can_set_meta_with_dot_notation()
    {
        $term = TermFacade::create([
            'name' => 'Electronics',
            'type' => 'category',
            'slug' => 'electronics',
            'meta' => [
                'settings' => [
                    'display' => 'grid'
                ]
            ]
        ]);

        $term->setMeta('settings.columns', 3);
        $this->assertEquals(3, $term->getMeta('settings.columns'));
    }

    /** @test */
    public function it_can_unset_meta_with_dot_notation()
    {
        $term = TermFacade::create([
            'name' => 'Electronics',
            'type' => 'category',
            'slug' => 'electronics',
            'meta' => [
                'settings' => [
                    'display' => 'grid',
                    'columns' => 3
                ]
            ]
        ]);

        $term->unsetMeta('settings.columns');
        $this->assertNull($term->getMeta('settings.columns'));
        $this->assertEquals('grid', $term->getMeta('settings.display'));
    }
}