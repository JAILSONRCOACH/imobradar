<?php

namespace Database\Seeders;

use App\Models\Fonte;
use Illuminate\Database\Seeder;

/** Fontes já usadas no monitoramento de Lucena. Novas fontes também são criadas pela API. */
class FontesSeeder extends Seeder
{
    public const FONTES = [
        ['slug' => 'mgf', 'nome' => 'MGF Imóveis', 'site_url' => 'https://www.mgfimoveis.com.br', 'tipo' => 'classificado'],
        ['slug' => 'olx', 'nome' => 'OLX', 'site_url' => 'https://www.olx.com.br', 'tipo' => 'classificado'],
        ['slug' => 'zap-vivareal', 'nome' => 'Zap / VivaReal', 'site_url' => 'https://www.zapimoveis.com.br', 'tipo' => 'portal'],
        ['slug' => 'chaves-na-mao', 'nome' => 'Chaves na Mão', 'site_url' => 'https://www.chavesnamao.com.br', 'tipo' => 'portal'],
        ['slug' => 'imovelweb', 'nome' => 'Imovelweb / Wimoveis', 'site_url' => 'https://www.imovelweb.com.br', 'tipo' => 'portal'],
        ['slug' => 'buskaza', 'nome' => 'Buskaza', 'site_url' => 'https://www.buskaza.com.br', 'tipo' => 'portal'],
        ['slug' => 'casa-mineira', 'nome' => 'Casa Mineira', 'site_url' => 'https://www.casamineira.com.br', 'tipo' => 'portal'],
        ['slug' => 'paraiba-property', 'nome' => 'Paraíba Property', 'site_url' => null, 'tipo' => 'imobiliaria'],
        ['slug' => 'casa-forte', 'nome' => 'Casa Forte Imobiliária', 'site_url' => null, 'tipo' => 'imobiliaria'],
        ['slug' => 'caixa', 'nome' => 'Caixa (leilão)', 'site_url' => 'https://venda-imoveis.caixa.gov.br', 'tipo' => 'leilao'],
        ['slug' => 'pgfn', 'nome' => 'PGFN Comprei (leilão)', 'site_url' => null, 'tipo' => 'leilao'],
        ['slug' => 'airbnb', 'nome' => 'Airbnb', 'site_url' => 'https://www.airbnb.com.br', 'tipo' => 'temporada', 'ativa' => false],
        ['slug' => 'vrbo', 'nome' => 'Vrbo', 'site_url' => 'https://www.vrbo.com', 'tipo' => 'temporada', 'ativa' => false],
        ['slug' => 'temporada-livre', 'nome' => 'Temporada Livre', 'site_url' => 'https://www.temporadalivre.com', 'tipo' => 'temporada'],
    ];

    public function run(): void
    {
        foreach (self::FONTES as $f) {
            Fonte::updateOrCreate(['slug' => $f['slug']], $f);
        }
    }
}
