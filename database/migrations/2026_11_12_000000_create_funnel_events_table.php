<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eventos de navegação do funil (presell + quiz) para o dashboard interno.
     * Tabela nova e independente: não altera nenhuma tabela existente.
     */
    public function up(): void
    {
        Schema::create('funnel_events', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->index();
            $table->string('event', 40);
            $table->unsignedTinyInteger('step')->nullable();
            $table->string('page', 120)->nullable();
            $table->string('profile', 20)->nullable();
            $table->string('state_name', 60)->nullable();
            $table->string('utm_source', 150)->nullable();
            $table->string('utm_medium', 150)->nullable();
            $table->string('utm_campaign', 150)->nullable();
            $table->string('utm_content', 150)->nullable();
            $table->string('utm_term', 150)->nullable();
            $table->text('props')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['event', 'step', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funnel_events');
    }
};
