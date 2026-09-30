<?php

namespace Tests\Feature;

use App\Models\Anuncio;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngestaoApiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'teste-token-com-mais-de-32-caracteres-0123456789';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['imobradar.ingest_token' => self::TOKEN]);
    }

    private function api(string $url, array $dados)
    {
        return $this->withToken(self::TOKEN)->postJson($url, $dados);
    }

    private function anuncio(string $id, string $preco): array
    {
        return [
            'id_externo' => $id, 'url' => "https://exemplo.com/{$id}", 'titulo' => "Casa {$id}",
            'tipo' => 'Casa', 'preco' => $preco, 'area_construida' => '100', 'bairro' => 'Fagundes',
        ];
    }

    public function test_exige_token(): void
    {
        $this->postJson('/api/coletas', ['fonte' => 'olx'])->assertStatus(401);
        $this->withToken('errado')->postJson('/api/coletas', ['fonte' => 'olx'])->assertStatus(401);
    }

    public function test_ciclo_completo_de_coleta(): void
    {
        $abrir = fn () => $this->api('/api/coletas', ['fonte' => 'olx', 'cidade_ibge' => 2508604, 'finalidade' => 'venda'])
            ->assertCreated()->json('coleta_id');

        $c1 = $abrir();
        $this->api("/api/coletas/{$c1}/anuncios", ['anuncios' => [
            $this->anuncio('A', '450000'), $this->anuncio('B', '300 mil'), ['id_externo' => 'X'],
        ]])->assertOk()->assertJson(['novos' => 2])->assertJsonCount(1, 'rejeitados');
        $this->api("/api/coletas/{$c1}/finalizar", ['completa' => true])->assertOk()->assertJson(['status' => 'concluida']);

        $c2 = $abrir();
        $this->api("/api/coletas/{$c2}/anuncios", ['anuncios' => [$this->anuncio('A', '420000')]])
            ->assertOk()->assertJson(['alterados' => 1]);
        $this->api("/api/coletas/{$c2}/finalizar", ['completa' => true])->assertJson(['removidos' => 1]);

        $this->assertSame('removido', Anuncio::where('id_externo', 'B')->value('status'));
        $this->assertEquals(4200.0, Anuncio::where('id_externo', 'A')->value('preco_m2'));

        $this->api("/api/coletas/{$c2}/anuncios", ['anuncios' => [$this->anuncio('C', '1')]])->assertStatus(409);
    }

    public function test_cidade_fora_da_paraiba_e_rejeitada(): void
    {
        $this->api('/api/coletas', ['fonte' => 'olx', 'cidade' => 'Recife'])->assertStatus(422);
    }

    public function test_paginas_publicas_abrem(): void
    {
        $c = $this->api('/api/coletas', ['fonte' => 'olx', 'cidade_ibge' => 2508604, 'finalidade' => 'venda'])->json('coleta_id');
        $this->api("/api/coletas/{$c}/anuncios", ['anuncios' => [$this->anuncio('A', '450000')]]);

        $this->get('/')->assertOk()->assertSee('Casa A');
        $this->get('/?cidade=lucena&tipo=casa&ordem=menor_preco')->assertOk()->assertSee('Casa A');
        $this->get('/imovel/'.Anuncio::first()->id)->assertOk()->assertSee('Histórico no radar');
    }
}
