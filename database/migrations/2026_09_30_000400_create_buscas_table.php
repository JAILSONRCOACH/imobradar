<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Buscas sob demanda: uma cidade + finalidade pedida por alguém e executada pelo n8n. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buscas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cidade_id')->constrained('cidades');
            $table->string('finalidade', 12);
            // buscando | concluida | falhou
            $table->string('status', 10)->default('buscando');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('encontrados')->default(0);
            $table->unsignedInteger('novos')->default(0);
            $table->text('erro')->nullable();
            $table->timestamp('concluida_em')->nullable();
            $table->timestamps();
            $table->index(['cidade_id', 'finalidade', 'status', 'created_at']);
        });

        Schema::table('coletas', function (Blueprint $table) {
            $table->foreignId('busca_id')->nullable()->after('finalidade')->constrained('buscas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('coletas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('busca_id');
        });
        Schema::dropIfExists('buscas');
    }
};
