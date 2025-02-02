<?php

use Aliziodev\LaravelTerms\Examples\Controllers\DummyTermController;

Route::prefix('dummy-terms')->group(function () {
    Route::post('/categories', [DummyTermController::class, 'createDummyCategories']);
    Route::post('/tags', [DummyTermController::class, 'createDummyTags']);
    Route::post('/products', [DummyTermController::class, 'createDummyProducts']);
    Route::post('/meta', [DummyTermController::class, 'createDummyMeta']);
    Route::post('/reorder', [DummyTermController::class, 'reorderDummyTerms']);
});
