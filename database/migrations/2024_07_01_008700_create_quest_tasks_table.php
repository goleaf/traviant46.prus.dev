<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quest_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quest_id')->constrained('quests');
            $table->string('task_code');
            $table->text('requirements');
            $table->unsignedInteger('task_order')->default(0);
            $table->unique(['quest_id', 'task_code']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_tasks');
    }
};
