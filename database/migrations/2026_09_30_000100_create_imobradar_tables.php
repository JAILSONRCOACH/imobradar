<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cidades', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ibge')->unique();
            $table->string('nome', 120);
            $table->string('slug', 140)->unique();
            $table->char('uf', 2)->default('PB');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('capital')->default(false);
            $table->timestamps();
        });

        Schema::create('bairros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cidade_id')->constrained('cidades')->cascadeOnDelete();
            $table->string('nome', 120);
            $table->string('slug', 140);
            $table->timestamps();
            $table->unique(['cidade_id', 'slug']);
        });

        Schema::create('fontes', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('nome', 120);
            $table->string('site_url', 255)->nullable();
            // portal | imobiliaria | classificado | temporada | leilao | outro
            $table->string('tipo', 20)->default('portal');
            $table->boolean('ativa')->default(true);
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });

        Schema::create('coletas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fonte_id')->constrained('fontes');
            $table->foreignId('cidade_id')->nullable()->constrained('cidades');
            $table->string('finalidade', 12)->nullable();
            // em_andamento | concluida | parcial | falhou
            $table->string('status', 15)->default('em_andamento');
            $table->timestamp('iniciada_em');
            $table->timestamp('finalizada_em')->nullable();
            $table->unsignedInteger('recebidos')->default(0);
            $table->unsignedInteger('novos')->default(0);
            $table->unsignedInteger('alterados')->default(0);
            $table->unsignedInteger('voltaram')->default(0);
            $table->unsignedInteger('removidos')->default(0);
            $table->unsignedInteger('rejeitados')->default(0);
            $table->text('erro')->nullable();
            $table->timestamps();
            $table->index(['fonte_id', 'cidade_id', 'finalidade', 'status']);
        });

        Schema::create('anuncios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fonte_id')->constrained('fontes');
            $table->string('id_externo', 191);
            $table->string('finalidade', 12);          // venda | aluguel | temporada
            $table->string('tipo', 30);                // ver App\Support\Normalizador::TIPOS
            $table->string('titulo', 500);
            $table->text('descricao')->nullable();
            $table->string('observacoes', 500)->nullable();
            $table->text('url');
            $table->json('outros_links')->nullable();  // [{fonte, url}]
            $table->string('foto_url', 1000)->nullable();

            $table->foreignId('cidade_id')->constrained('cidades');
            $table->foreignId('bairro_id')->nullable()->constrained('bairros')->nullOnDelete();
            $table->string('localizacao_texto', 255)->nullable();
            $table->string('endereco', 255)->nullable();
            $table->string('condominio_nome', 150)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            $table->decimal('preco', 14, 2)->nullable();
            // total (venda) | mes | diaria | pacote | a_confirmar
            $table->string('preco_unidade', 15)->nullable();
            $table->string('preco_texto', 120)->nullable();
            $table->decimal('valor_condominio', 12, 2)->nullable();
            $table->decimal('valor_iptu', 12, 2)->nullable();
            $table->decimal('area_construida', 12, 2)->nullable();
            $table->decimal('area_terreno', 14, 2)->nullable();
            $table->decimal('preco_m2', 12, 2)->nullable();

            $table->unsignedTinyInteger('quartos')->nullable();
            $table->unsignedTinyInteger('suites')->nullable();
            $table->unsignedTinyInteger('banheiros')->nullable();
            $table->unsignedTinyInteger('vagas')->nullable();
            $table->unsignedSmallInteger('hospedes')->nullable();
            $table->boolean('piscina')->nullable();
            $table->boolean('proximo_praia')->nullable();
            $table->json('caracteristicas')->nullable();

            // ativo | removido
            $table->string('status', 10)->default('ativo');
            $table->unsignedBigInteger('grupo_id')->nullable();
            $table->string('duplicado_obs', 255)->nullable();
            $table->char('hash_conteudo', 40)->nullable();
            $table->date('primeira_vez_em');
            $table->date('ultima_vez_em');
            $table->date('removido_em')->nullable();
            $table->unsignedBigInteger('ultima_coleta_id')->nullable();
            $table->timestamps();

            $table->unique(['fonte_id', 'id_externo', 'finalidade']);
            $table->index(['cidade_id', 'finalidade', 'status', 'tipo']);
            $table->index(['status', 'finalidade', 'preco']);
            $table->index('preco_m2');
            $table->index('primeira_vez_em');
            $table->index('grupo_id');
            $table->index('ultima_coleta_id');
        });

        Schema::create('anuncio_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anuncio_id')->constrained('anuncios')->cascadeOnDelete();
            // novo | preco | removido | voltou
            $table->string('tipo', 10);
            $table->decimal('preco_anterior', 14, 2)->nullable();
            $table->decimal('preco_novo', 14, 2)->nullable();
            $table->date('ocorrido_em');
            $table->foreignId('coleta_id')->nullable()->constrained('coletas')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->index(['ocorrido_em', 'tipo']);
            $table->index(['anuncio_id', 'ocorrido_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anuncio_eventos');
        Schema::dropIfExists('anuncios');
        Schema::dropIfExists('coletas');
        Schema::dropIfExists('fontes');
        Schema::dropIfExists('bairros');
        Schema::dropIfExists('cidades');
    }
};
