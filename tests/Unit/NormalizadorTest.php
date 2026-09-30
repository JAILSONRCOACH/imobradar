<?php

namespace Tests\Unit;

use App\Support\Normalizador as N;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NormalizadorTest extends TestCase
{
    public static function numeros(): array
    {
        return [
            ['R$ 1.234.567,89', 1234567.89], ['320 mil', 320000.0], ['R$ 1,2 milhão', 1200000.0],
            [689400, 689400.0], ['689.400', 689400.0], ['1.5', 1.5], ['0', null], ['', null], ['sob consulta', null],
        ];
    }

    #[DataProvider('numeros')]
    public function test_numero(mixed $entrada, ?float $esperado): void
    {
        $this->assertSame($esperado, N::numero($entrada));
    }

    public function test_tipo(): void
    {
        $this->assertSame('casa_condominio', N::tipo('Casa em condomínio'));
        $this->assertSame('chacara_sitio', N::tipo('Chácara/Sítio'));
        $this->assertSame('terreno', N::tipo('Terreno em Condomínio para Venda em Lucena'));
        $this->assertSame('sala_comercial', N::tipo('Sala comercial no centro'));
        $this->assertSame('outro', N::tipo('Tropical Getaway Bungalow'));
    }

    public function test_preco_m2(): void
    {
        $this->assertSame(3225.81, N::precoM2('venda', 800000.0, 'casa', 248.0, null));
        $this->assertSame(1600.0, N::precoM2('venda', 3395200.0, 'terreno', null, 2122.0));
        $this->assertNull(N::precoM2('aluguel', 2000.0, 'casa', 100.0, null));
        $this->assertNull(N::precoM2('venda', 300000.0, 'casa', null, 360.0));
    }

    public function test_inteiro_e_acessorio(): void
    {
        $this->assertSame(5, N::inteiro('5+'));
        $this->assertNull(N::valorAcessorio('1'));
        $this->assertSame(240.0, N::valorAcessorio('240'));
    }
}
