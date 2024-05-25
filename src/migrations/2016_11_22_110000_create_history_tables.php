<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHistoryTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('model_histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->nullableMorphs('user');
            $table->string('operation')->index();
            $table->json('previous')->nullable();
            $table->json('changes')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('performed_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('model_histories');
    }
}
