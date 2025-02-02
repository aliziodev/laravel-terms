<?php

namespace Aliziodev\LaravelTerms\Http\Controllers\Term;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Aliziodev\LaravelTerms\Facades\Term as TermFacade;
use Aliziodev\LaravelTerms\Models\Term;

class TermController extends Controller
{
    /**
     * Display a listing of the terms.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $type = $request->query('type');
            $terms = $type ? TermFacade::getByType($type) : TermFacade::all();
            
            return response()->json([
                'success' => true,
                'data' => $terms
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created term.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $term = TermFacade::create($request->all());
            
            return response()->json([
                'success' => true,
                'data' => $term
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified term.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $term = TermFacade::getById($id);
            
            return response()->json([
                'success' => true,
                'data' => $term
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified term.
     */
    public function update(Request $request, Term $term): JsonResponse
    {
        try {
            $term = TermFacade::update($term, $request->all());
            
            return response()->json([
                'success' => true,
                'data' => $term
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified term.
     */
    public function destroy(Term $term): JsonResponse
    {
        try {
            TermFacade::delete($term);
            
            return response()->json([
                'success' => true,
                'message' => 'Term deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 