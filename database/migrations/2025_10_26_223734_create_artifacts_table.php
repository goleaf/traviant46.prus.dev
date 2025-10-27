<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration responsible for establishing the artifacts catalog used to define global bonuses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifacts', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique()->comment('Stable identifier used to reference a specific artifact definition.');
            $table->string('name')->comment('Localized display name surfaced to players.');
            $table->enum('size', ['small', 'large', 'unique'])->comment('Scale bucket that determines how many artifacts exist for this definition.');
            $table->enum('scope', ['village', 'account'])->comment('Indicates whether the effect applies to a single village or the whole account.');
            $table->json('effect')->comment('Structured definition of effect modifiers persisted as JSON.');
            $table->unsignedTinyInteger('treasury_level_req')->comment('Minimum treasury level required before the artifact can be activated.');
            $table->timestamps();
            $table->index(['size', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifacts');
    }
};
