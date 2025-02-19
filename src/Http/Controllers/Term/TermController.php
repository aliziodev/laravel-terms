<?php

namespace Aliziodev\LaravelTerms\Http\Controllers\Term;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Aliziodev\LaravelTerms\Models\Term;
use Aliziodev\LaravelTerms\Http\Requests\Term\{
    StoreTermRequest,
    UpdateTermRequest
};

class TermController extends Controller
{
    /**
     * Display a listing of terms.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Term::query();

        // Filter by type
        if ($request->has('type')) {
            $query->type($request->type);
        }

        // Filter by parent
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // Filter root terms
        if ($request->boolean('root')) {
            $query->root();
        }

        // Filter leaf terms
        if ($request->boolean('leaf')) {
            $query->leaf();
        }

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Get as tree, flat tree, or paginated
        if ($request->boolean('tree')) {
            return response()->json(Term::tree($request->type));
        }

        if ($request->boolean('flat_tree')) {
            return response()->json(Term::treeFlat($request->type));
        }

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    /**
     * Store a newly created term.
     */
    public function store(StoreTermRequest $request): JsonResponse
    {
        $term = DB::transaction(function () use ($request) {
            return Term::create($request->validated());
        });

        return response()->json($term->toTree(), 201);
    }

    /**
     * Display the specified term.
     */
    public function show(Request $request, Term $term): JsonResponse
    {
        $data = $term->toArray();

        if ($request->boolean('tree')) {
            return response()->json($term->toTree());
        }

        if ($request->boolean('ancestors')) {
            $data['ancestors'] = $term->getAncestors();
        }

        if ($request->boolean('descendants')) {
            $data['descendants'] = $term->getDescendants();
        }

        // Add computed attributes
        $data['path'] = $term->path;
        $data['depth'] = $term->depth;
        $data['is_leaf'] = $term->is_leaf;
        $data['is_root'] = $term->is_root;

        return response()->json($data);
    }

    /**
     * Update the specified term.
     */
    public function update(UpdateTermRequest $request, Term $term): JsonResponse
    {
        DB::transaction(function () use ($request, $term) {
            $term->update($request->validated());
        });

        return response()->json($term->toTree());
    }

    /**
     * Remove the specified term.
     */
    public function destroy(Term $term): JsonResponse
    {
        DB::transaction(function () use ($term) {
            $term->delete();
        });

        return response()->json(null, 204);
    }

    /**
     * Move term in hierarchy.
     */
    public function move(Request $request, Term $term): JsonResponse
    {
        $request->validate([
            'parent_id' => 'nullable|exists:terms,id',
            'position' => 'nullable|in:before,after,start,end',
            'target_id' => 'required_with:position|exists:terms,id',
            'order' => 'nullable|integer|min:1'
        ]);

        DB::transaction(function () use ($request, $term) {
            // Update parent
            if ($request->has('parent_id')) {
                $term->parent_id = $request->parent_id;
                $term->save();
            }

            // Move to specific position
            if ($request->has('position')) {
                $target = Term::findOrFail($request->target_id);
                
                match ($request->position) {
                    'before' => $term->moveBefore($target),
                    'after' => $term->moveAfter($target),
                    'start' => $term->moveToStart(),
                    'end' => $term->moveToEnd(),
                };
            }

            // Move to specific order
            if ($request->has('order')) {
                $term->moveToOrder($request->order);
            }
        });

        return response()->json($term->toTree());
    }

    /**
     * Get term statistics.
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Term::count(),
            'by_type' => Term::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'root' => Term::root()->count(),
            'leaf' => Term::leaf()->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Search terms.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'keyword' => 'required|string|min:2',
            'type' => 'nullable|string'
        ]);

        $query = Term::search($request->keyword);

        if ($request->has('type')) {
            $query->type($request->type);
        }

        return response()->json($query->get());
    }
} 