<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $tableNames = config('terms.table_names');

        Schema::create($tableNames['terms'], function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->index();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('terms');
            $table->integer('order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($tableNames['termables'], function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->uuidMorphs('termable');
            $table->timestamps();
        });
    }

    public function down()
    {
        $tableNames = config('terms.table_names');
        
        Schema::dropIfExists($tableNames['termables']);
        Schema::dropIfExists($tableNames['terms']);
    }
};