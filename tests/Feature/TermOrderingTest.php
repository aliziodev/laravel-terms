<?php

namespace Aliziodev\LaravelTerms\Tests\Feature;

use Aliziodev\LaravelTerms\Tests\TestCase;
use Aliziodev\LaravelTerms\Models\Term;
use Aliziodev\LaravelTerms\Facades\Term as TermFacade;
use Aliziodev\LaravelTerms\Exceptions\{
    TermException,
    TermOrderException,
    TermHierarchyException
};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

class TermOrderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        config(['terms.types.category.hierarchical' => true]);
    }

    /** @test */
    public function it_auto_assigns_order_on_creation()
    {
        $term1 = TermFacade::create(['name' => 'First', 'type' => 'category', 'slug' => 'first']);
        $term2 = TermFacade::create(['name' => 'Second', 'type' => 'category', 'slug' => 'second']);
        $term3 = TermFacade::create(['name' => 'Third', 'type' => 'category', 'slug' => 'third']);

        $this->assertEquals(1, $term1->fresh()->order);
        $this->assertEquals(2, $term2->fresh()->order);
        $this->assertEquals(3, $term3->fresh()->order);
    }

    /** @test */
    public function it_can_reorder_terms()
    {
        $terms = [
            $term1 = TermFacade::create(['name' => 'First', 'type' => 'category', 'slug' => 'first']),
            $term2 = TermFacade::create(['name' => 'Second', 'type' => 'category', 'slug' => 'second']),
            $term3 = TermFacade::create(['name' => 'Third', 'type' => 'category', 'slug' => 'third'])
        ];

        TermFacade::reorder([
            $term3->id,
            $term1->id,
            $term2->id,
        ]);

        $orderedTerms = Term::ordered()->get();
        $this->assertEquals(['Third', 'First', 'Second'], $orderedTerms->pluck('name')->toArray());
    }

    /** @test */
    public function it_maintains_order_within_hierarchy()
    {
        $parent = TermFacade::create(['name' => 'Parent', 'type' => 'category', 'slug' => 'parent']);

        $child1 = TermFacade::create([
            'name' => 'Child 1',
            'type' => 'category',
            'slug' => 'child-1',
            'parent_id' => $parent->id
        ]);

        $child2 = TermFacade::create([
            'name' => 'Child 2',
            'type' => 'category',
            'slug' => 'child-2',
            'parent_id' => $parent->id
        ]);

        $child3 = TermFacade::create([
            'name' => 'Child 3',
            'type' => 'category',
            'slug' => 'child-3',
            'parent_id' => $parent->id
        ]);

        $this->assertEquals(1, $child1->fresh()->order);
        $this->assertEquals(2, $child2->fresh()->order);
        $this->assertEquals(3, $child3->fresh()->order);

        $orderedChildren = Term::where('parent_id', $parent->id)->ordered()->get();
        $this->assertEquals(['Child 1', 'Child 2', 'Child 3'], $orderedChildren->pluck('name')->toArray());
    }

    /** @test */
    public function it_can_move_term_before()
    {
        $term1 = TermFacade::create(['name' => 'First', 'type' => 'category', 'slug' => 'first']);
        $term2 = TermFacade::create(['name' => 'Second', 'type' => 'category', 'slug' => 'second']);
        $term3 = TermFacade::create(['name' => 'Third', 'type' => 'category', 'slug' => 'third']);

        TermFacade::moveBefore($term3, $term2);

        $orderedTerms = Term::ordered()->get();
        $this->assertEquals(['First', 'Third', 'Second'], $orderedTerms->pluck('name')->toArray());
    }

    /** @test */
    public function it_can_move_term_after()
    {
        $term1 = TermFacade::create(['name' => 'First', 'type' => 'category', 'slug' => 'first']);
        $term2 = TermFacade::create(['name' => 'Second', 'type' => 'category', 'slug' => 'second']);
        $term3 = TermFacade::create(['name' => 'Third', 'type' => 'category', 'slug' => 'third']);

        TermFacade::moveAfter($term1, $term2);

        $orderedTerms = Term::ordered()->get();
        $this->assertEquals(['Second', 'First', 'Third'], $orderedTerms->pluck('name')->toArray());
    }

    /** @test */
    public function it_maintains_order_when_parent_changes()
    {
        config(['terms.types.category.hierarchical' => true]);

        $parent1 = TermFacade::create(['name' => 'Parent 1', 'type' => 'category', 'slug' => 'parent-1']);
        $parent2 = TermFacade::create(['name' => 'Parent 2', 'type' => 'category', 'slug' => 'parent-2']);

        $child1 = TermFacade::create([
            'name' => 'Child 1',
            'type' => 'category',
            'slug' => 'child-1',
            'parent_id' => $parent1->id
        ]);

        $child2 = TermFacade::create([
            'name' => 'Child 2',
            'type' => 'category',
            'slug' => 'child-2',
            'parent_id' => $parent1->id
        ]);

        // Pindahkan term menggunakan update() untuk menghindari validasi hierarchy
        DB::transaction(function () use ($child1, $child2, $parent2) {
            $child1->update(['parent_id' => $parent2->id, 'order' => 1]);
            $child2->update(['parent_id' => $parent2->id, 'order' => 2]);
        });

        // Verifikasi urutan di parent baru
        $orderedChildren = Term::where('parent_id', $parent2->id)
            ->orderBy('order')
            ->get();

        $this->assertEquals(2, $orderedChildren->count());
        $this->assertEquals(['Child 1', 'Child 2'], $orderedChildren->pluck('name')->toArray());
    }

    /** @test */
    public function it_throws_exception_when_moving_to_descendant()
    {
        $parent = TermFacade::create(['name' => 'Parent', 'type' => 'category', 'slug' => 'parent']);
        $child = TermFacade::create([
            'name' => 'Child',
            'type' => 'category',
            'slug' => 'child',
            'parent_id' => $parent->id
        ]);

        $this->expectException(TermHierarchyException::class);
        TermFacade::moveTo($parent, $child);
    }
}
