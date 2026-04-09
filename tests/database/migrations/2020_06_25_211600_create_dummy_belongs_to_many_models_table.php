<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dummy_belongs_to_many_models', static function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dummy_belongs_to_many_model_dummy_model', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('dummy_model_id');
            $table->unsignedInteger('dummy_belongs_to_many_model_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dummy_belongs_to_many_models');
        Schema::dropIfExists('dummy_belongs_to_many_model_dummy_model');
    }
};
