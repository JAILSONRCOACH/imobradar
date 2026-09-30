<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Padroniza valores que chegam das fontes (tipo, finalidade, números em texto).
 * Não depende de banco: pode ser testado isoladamente.
 */
class Normalizador
{
    public const FINALIDADES = ['venda', 'aluguel', 'temporada'];

    public const UNIDADES_PRECO = ['total', 'mes', 'diaria', 'pacote', 'a_confirmar'];

    /** slug => rótulo exibido */
    public const TIPOS = [
        'casa' => 'Casa',
        'casa_condominio' => 'Casa em condomínio',
        'apartamento' => 'Apartamento',
        'cobertura' => 'Cobertura',
        'flat' => 'Flat',
        'kitnet' => 'Kitnet/Studio',
        'terreno' => 'Terreno',
        'chacara_sitio' => 'Chácara/Sítio',
        'fazenda' => 'Fazenda',
        'sala_comercial' => 'Sala comercial',
        'loja' => 'Loja',
        'galpao' => 'Galpão',
        'comercial' => 'Imóvel comercial',
        'outro' => 'Outro',
    ];

    /** Termos (sem acento, minúsculos) => slug. A ordem importa: o mais específico primeiro. */
    private const MAPA_TIPOS = [
        'casa em condominio' => 'casa_condominio',
        'casa de condominio' => 'casa_condominio',
        'condominio fechado' => 'casa_condominio',
        'sala comercial' => 'sala_comercial',
        'ponto comercial' => 'loja',
        'terreno em condominio' => 'terreno',
        'cobertura' => 'cobertura',
        'apartamento' => 'apartamento',
        'apto' => 'apartamento',
        'flat' => 'flat',
        'kitnet' => 'kitnet',
        'kitinete' => 'kitnet',
        'studio' => 'kitnet',
        'loft' => 'kitnet',
        'terreno' => 'terreno',
        'lote' => 'terreno',
        'chacara' => 'chacara_sitio',
        'sitio' => 'chacara_sitio',
        'granja' => 'chacara_sitio',
        'fazenda' => 'fazenda',
        'galpao' => 'galpao',
        'deposito' => 'galpao',
        'loja' => 'loja',
        'sala' => 'sala_comercial',
        'comercial' => 'comercial',
        'predio' => 'comercial',
        'casa' => 'casa',
        'bangalo' => 'casa',
        'sobrado' => 'casa',
        'residencia' => 'casa',
    ];

    public static function tipo(?string $valor): string
    {
        if ($valor === null || trim($valor) === '') {
            return 'outro';
        }

        $v = trim($valor);
        if (array_key_exists($v, self::TIPOS)) {
            return $v;
        }

        $t = Str::lower(Str::ascii($v));
        $t = str_replace(['/', '-', '_'], ' ', $t);
        foreach (self::MAPA_TIPOS as $termo => $slug) {
            if (preg_match('/\b'.preg_quote($termo, '/').'\b/', $t)) {
                return $slug;
            }
        }

        return 'outro';
    }

    public static function finalidade(?string $valor): ?string
    {
        $v = Str::lower(Str::ascii(trim((string) $valor)));

        return match (true) {
            in_array($v, ['venda', 'compra', 'sale'], true) => 'venda',
            in_array($v, ['aluguel', 'locacao', 'mensal', 'rent'], true) => 'aluguel',
            in_array($v, ['temporada', 'diaria', 'veraneio'], true) => 'temporada',
            default => null,
        };
    }

    /**
     * Converte "R$ 1.234.567,89", "320 mil", "1,2 milhão", "5+", 450000 em número.
     * Retorna null para vazio, zero ou texto sem número.
     */
    public static function numero(mixed $valor): ?float
    {
        if ($valor === null || $valor === '' || is_bool($valor)) {
            return null;
        }
        if (is_int($valor) || is_float($valor)) {
            return $valor > 0 ? (float) $valor : null;
        }

        $s = Str::lower(Str::ascii(trim((string) $valor)));
        $mult = 1;
        if (preg_match('/\b(milhao|milhoes|mi)\b/', $s)) {
            $mult = 1_000_000;
        } elseif (preg_match('/\bmil\b|\d\s*k\b/', $s)) {
            $mult = 1_000;
        }

        if (! preg_match('/\d[\d.,]*/', $s, $m)) {
            return null;
        }
        $n = rtrim($m[0], '.,');

        $temPonto = str_contains($n, '.');
        $temVirgula = str_contains($n, ',');
        if ($temPonto && $temVirgula) {
            // formato BR: 1.234,56
            $n = str_replace('.', '', $n);
            $n = str_replace(',', '.', $n);
        } elseif ($temVirgula) {
            // "1,5" (decimal) ou "1,234" (milhar em formato US)
            $n = preg_match('/,\d{3}$/', $n) && $mult === 1 ? str_replace(',', '', $n) : str_replace(',', '.', $n);
        } elseif ($temPonto) {
            // "1.234.567" é milhar; "1.5" é decimal
            $n = preg_match('/^\d{1,3}(\.\d{3})+$/', $n) ? str_replace('.', '', $n) : $n;
        }

        $f = (float) $n * $mult;

        return $f > 0 ? round($f, 2) : null;
    }

    /** Inteiro pequeno (quartos, vagas...). "5+" vira 5; fora de 0..250 vira null. */
    public static function inteiro(mixed $valor): ?int
    {
        if ($valor === null || $valor === '' || is_bool($valor)) {
            return null;
        }
        if (! preg_match('/\d+/', (string) $valor, $m)) {
            return null;
        }
        $i = (int) $m[0];

        return $i >= 0 && $i <= 250 ? $i : null;
    }

    /** Valor monetário acessório (condomínio, IPTU). Valores <= 1 costumam ser lixo de portal. */
    public static function valorAcessorio(mixed $valor): ?float
    {
        $n = self::numero($valor);

        return $n !== null && $n > 1 ? $n : null;
    }

    public static function booleano(mixed $valor): ?bool
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (is_bool($valor)) {
            return $valor;
        }

        return in_array(Str::lower((string) $valor), ['1', 'true', 'sim', 'yes', 's'], true);
    }

    public static function texto(mixed $valor, int $max): ?string
    {
        if ($valor === null) {
            return null;
        }
        $s = trim(preg_replace('/\s+/u', ' ', (string) $valor));

        return $s === '' ? null : Str::limit($s, $max - 3, '...');
    }

    /** R$/m² só faz sentido para venda com preço total e área conhecida. */
    public static function precoM2(?string $finalidade, ?float $preco, string $tipo, ?float $areaConstruida, ?float $areaTerreno): ?float
    {
        if ($finalidade !== 'venda' || ! $preco) {
            return null;
        }
        $area = in_array($tipo, ['terreno', 'chacara_sitio', 'fazenda'], true)
            ? ($areaTerreno ?: $areaConstruida)
            : $areaConstruida;

        if (! $area || $area < 10) {
            return null;
        }

        return round($preco / $area, 2);
    }
}
