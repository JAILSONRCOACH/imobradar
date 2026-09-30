<?php

namespace App\Support;

use Illuminate\Support\Str;

/** Reconhece o portal pelo nome ou pelo domínio do anúncio, para não criar fontes duplicadas. */
class Fontes
{
    private const DOMINIOS = [
        'olx.com.br' => 'olx',
        'zapimoveis.com.br' => 'zap-vivareal',
        'vivareal.com.br' => 'zap-vivareal',
        'chavesnamao.com.br' => 'chaves-na-mao',
        'imovelweb.com.br' => 'imovelweb',
        'wimoveis.com.br' => 'imovelweb',
        'mgfimoveis.com.br' => 'mgf',
        'buskaza.com.br' => 'buskaza',
        'casamineira.com.br' => 'casa-mineira',
        'airbnb.com' => 'airbnb',
        'airbnb.com.br' => 'airbnb',
        'vrbo.com' => 'vrbo',
        'temporadalivre.com' => 'temporada-livre',
        'venda-imoveis.caixa.gov.br' => 'caixa',
    ];

    public static function slug(string $nome, ?string $url = null): string
    {
        $host = $url ? Str::lower((string) parse_url($url, PHP_URL_HOST)) : '';
        $host = preg_replace('/^(www|m|pb|joaopessoa)\./', '', $host);
        foreach (self::DOMINIOS as $dominio => $slug) {
            if ($host === $dominio || str_ends_with($host, '.'.$dominio)) {
                return $slug;
            }
        }
        if ($host !== '') {
            // Site próprio de imobiliária: o domínio é o identificador mais estável.
            return Str::limit(Str::slug(preg_replace('/\.(com|net|imb)?\.?br$|\.com$/', '', $host)), 60, '');
        }

        return Str::limit(Str::slug($nome), 60, '');
    }
}
