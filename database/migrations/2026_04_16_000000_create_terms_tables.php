<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = array_merge([
            'terms' => 'terms',
            'termables' => 'termables',
        ], (array) config('terms.table_names', []));

        $morphType = config('terms.morph_type', 'numeric');

        Schema::create($tableNames['terms'], function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('type')->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['type', 'slug']);
            $table->index(['type', 'sort_order']);
        });

        Schema::create($tableNames['termables'], function (Blueprint $table) use ($morphType, $tableNames): void {
            $table->id();
            $table->foreignId('term_id')->constrained($tableNames['terms'])->cascadeOnDelete();

            switch ($morphType) {
                case 'uuid':
                    $table->uuidMorphs('termable');
                    break;
                case 'ulid':
                    $table->ulidMorphs('termable');
                    break;
                default:
                    $table->morphs('termable');
                    break;
            }

            $table->string('context')->nullable()->index();
            $table->timestamps();

            $table->unique(['term_id', 'termable_type', 'termable_id', 'context'], 'termables_unique_link');
        });
    }

    public function down(): void
    {
        $tableNames = array_merge([
            'terms' => 'terms',
            'termables' => 'termables',
        ], (array) config('terms.table_names', []));

        Schema::dropIfExists($tableNames['termables']);
        Schema::dropIfExists($tableNames['terms']);
    }
};
