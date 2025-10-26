<?php

declare(strict_types=1);

use App\Models\Game\World;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('villages')) {
            return;
        }

        Schema::create('villages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(World::class, 'world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('x');
            $table->integer('y');
            $table->boolean('is_capital')->default(false);
            $table->unsignedInteger('population')->default(0);
            $table->unsignedTinyInteger('loyalty')->default(100);
            $table->unsignedInteger('culture_points')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('villages');
    }
};
