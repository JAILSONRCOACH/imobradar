<?php

namespace App\Support;

/** Formatação para as views (padrão brasileiro). */
class Formata
{
    public static function moeda(?float $v, bool $compacto = false): string
    {
        if ($v === null) {
            return 'Sob consulta';
        }
        if ($compacto && $v >= 1_000_000) {
            return 'R$ '.rtrim(rtrim(number_format($v / 1_000_000, 2, ',', '.'), '0'), ',').' mi';
        }
        if ($compacto && $v >= 10_000) {
            return 'R$ '.number_format($v / 1_000, 0, ',', '.').' mil';
        }

        return 'R$ '.number_format($v, $v == floor($v) ? 0 : 2, ',', '.');
    }

    public static function numero(?float $v, int $casas = 0): string
    {
        return $v === null ? '—' : number_format($v, $casas, ',', '.');
    }

    public static function unidade(?string $u): string
    {
        return match ($u) {
            'mes' => '/mês',
            'diaria' => '/diária',
            'pacote' => ' (pacote)',
            'a_confirmar' => ' (unidade a confirmar)',
            default => '',
        };
    }

    public static function unidadeCurta(?string $u): ?string
    {
        return match ($u) {
            'mes' => 'por mês',
            'diaria' => 'por diária',
            'pacote' => 'pacote',
            'a_confirmar' => 'período a confirmar',
            default => null,
        };
    }

    public static function preco(?float $preco, ?string $unidade, bool $compacto = false): string
    {
        return $preco === null ? 'Sob consulta' : self::moeda($preco, $compacto).self::unidade($unidade);
    }

    public static function variacao(float $de, float $para): string
    {
        $pct = $de > 0 ? (($para - $de) / $de) * 100 : 0;

        return ($pct > 0 ? '+' : '').number_format($pct, 1, ',', '.').'%';
    }
}
