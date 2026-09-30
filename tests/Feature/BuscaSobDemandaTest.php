<?php

namespace Tests\Feature;

use App\Models\Anuncio;
use App\Models\Busca;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BuscaSobDemandaTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'teste-token-com-mais-de-32-caracteres-0123456789';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config([
            'imobradar.ingest_token' => self::TOKEN,
            'imobradar.busca.n8n_webhook' => 'https://n8n.teste/webhook/imobradar-busca',
            'imobradar.busca.n8n_segredo' => 'segredo',
            'app.url' => 'https://imobradar.com.br',
        ]);
        $this->actingAs(User::factory()->create(['status' => 'aprovado']));
    }

    public function test_pesquisar_cidade_dispara_n8n_uma_vez(): void
    {
        Http::fake(['n8n.teste/*' => Http::response(['message' => 'Workflow was started'])]);

        $this->get('/?cidade=conde')->assertOk()->assertSee('Buscando anúncios em Conde agora');
        $this->get('/?cidade=conde')->assertOk();

        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r->hasHeader('X-Imobradar-Segredo', 'segredo') && $r['cidade'] === 'Conde' && $r['finalidade'] === 'venda');
        $this->assertSame('buscando', Busca::first()->status);
    }

    public function test_n8n_devolve_anuncios_e_busca_conclui(): void
    {
        Http::fake(['n8n.teste/*' => Http::response([])]);
        $this->get('/?cidade=conde');
        $busca = Busca::first();

        $coleta = $this->withToken(self::TOKEN)->postJson('/api/coletas', [
            'fonte' => 'busca-web', 'cidade_ibge' => 2504603, 'finalidade' => 'venda', 'busca_id' => $busca->id,
        ])->assertCreated()->json('coleta_id');

        $this->withToken(self::TOKEN)->postJson("/api/coletas/{$coleta}/anuncios", ['anuncios' => [
            ['url' => 'https://www.olx.com.br/imoveis/casa-em-jacuma-123', 'titulo' => 'Casa em Jacumã', 'fonte' => 'OLX', 'preco' => 350000, 'tipo' => 'casa', 'bairro' => 'Jacumã'],
            ['url' => 'https://www.imobiliariaexemplo.com.br/imovel/9', 'titulo' => 'Terreno em Carapibus', 'fonte' => 'Imobiliária Exemplo', 'preco' => '120 mil', 'tipo' => 'terreno'],
        ]])->assertOk()->assertJson(['novos' => 2]);

        $this->getJson(route('busca.status', $busca))->assertJson(['status' => 'buscando', 'encontrados' => 2]);

        $this->withToken(self::TOKEN)->postJson("/api/coletas/{$coleta}/finalizar", ['completa' => false])->assertOk();
        $this->assertSame('concluida', $busca->fresh()->status);
        $this->getJson(route('busca.status', $busca))->assertJson(['status' => 'concluida', 'encontrados' => 2]);

        // Cada anúncio fica com o portal de origem, não com "busca na web".
        $this->assertSame('olx', Anuncio::where('titulo', 'Casa em Jacumã')->first()->fonte->slug);

        // Dados frescos: pesquisar de novo não dispara outra busca e mostra os imóveis.
        $this->get('/?cidade=Jacumã (Conde)')->assertOk()->assertSee('Casa em Jacumã')->assertDontSee('Buscando anúncios');
        Http::assertSentCount(1);
    }

    public function test_n8n_fora_do_ar_nao_quebra_a_pagina(): void
    {
        Http::fake(['n8n.teste/*' => Http::response('erro', 500)]);
        $this->get('/?cidade=conde')->assertOk()->assertSee('indisponível agora');
        $this->assertSame('falhou', Busca::first()->status);
    }

    public function test_sem_webhook_configurado_nao_busca(): void
    {
        config(['imobradar.busca.n8n_webhook' => '']);
        Http::fake();
        $this->get('/?cidade=conde')->assertOk();
        Http::assertNothingSent();
        $this->assertDatabaseCount('buscas', 0);
    }
}
