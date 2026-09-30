<?php

namespace App\Support;

use App\Models\Anuncio;

/** Escolhe a imagem de um anúncio: a foto real, se houver, ou uma ilustrativa do mesmo tipo. */
class Foto
{
    private const GRUPO_DO_TIPO = [
        'casa' => 'casas', 'casa_condominio' => 'casas',
        'apartamento' => 'aptos', 'cobertura' => 'aptos', 'flat' => 'aptos', 'kitnet' => 'aptos',
        'terreno' => 'terrenos', 'chacara_sitio' => 'terrenos', 'fazenda' => 'terrenos',
    ];

    public static function url(string $codigo, int $largura = 900): string
    {
        return "https://images.unsplash.com/photo-{$codigo}?auto=format&fit=crop&w={$largura}&q=70";
    }

    /** @return array{url: string, ilustrativa: bool} */
    public static function doAnuncio(Anuncio $a, int $largura = 640): array
    {
        if ($a->foto_url) {
            return ['url' => $a->foto_url, 'ilustrativa' => false];
        }
        $lista = config('imobradar.fotos.'.(self::GRUPO_DO_TIPO[$a->tipo] ?? 'outros'), []);
        if (! $lista) {
            return ['url' => '', 'ilustrativa' => true];
        }

        return ['url' => self::url($lista[$a->id % count($lista)], $largura), 'ilustrativa' => true];
    }

    public static function paisagem(int $indice = 0, int $largura = 1800): string
    {
        $lista = config('imobradar.fotos.paisagem', []);

        return $lista ? self::url($lista[$indice % count($lista)], $largura) : '';
    }
}
