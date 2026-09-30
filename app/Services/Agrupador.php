<?php

namespace App\Services;

use App\Models\Anuncio;
use Illuminate\Support\Facades\DB;

/**
 * Junta anúncios que provavelmente são o mesmo imóvel num grupo (grupo_id = menor id do grupo).
 * Grupos existentes são fundidos quando um par liga dois grupos diferentes.
 */
class Agrupador
{
    /**
     * @param  array<int, array{0:int, 1:int}>  $pares  pares de ids de anúncio
     * @return int quantidade de anúncios cujo grupo mudou
     */
    public function agrupar(array $pares): int
    {
        if ($pares === []) {
            return 0;
        }

        $ids = array_values(array_unique(array_merge(...array_map('array_values', $pares))));

        return DB::transaction(function () use ($ids, $pares) {
            // Inclui todos os membros dos grupos já existentes desses anúncios.
            $grupos = Anuncio::whereIn('id', $ids)->whereNotNull('grupo_id')->pluck('grupo_id')->unique()->all();
            $membros = Anuncio::query()
                ->where(fn ($q) => $q->whereIn('id', $ids)->orWhereIn('grupo_id', $grupos ?: [0]))
                ->get(['id', 'grupo_id']);

            $pai = [];
            $achar = function (int $x) use (&$pai, &$achar): int {
                if (! isset($pai[$x])) {
                    $pai[$x] = $x;
                }
                if ($pai[$x] !== $x) {
                    $pai[$x] = $achar($pai[$x]);
                }

                return $pai[$x];
            };
            $unir = function (int $a, int $b) use (&$pai, $achar): void {
                $ra = $achar($a);
                $rb = $achar($b);
                if ($ra !== $rb) {
                    $pai[max($ra, $rb)] = min($ra, $rb);
                }
            };

            foreach ($membros as $m) {
                $achar($m->id);
                if ($m->grupo_id) {
                    $unir($m->id, (int) $m->grupo_id);
                }
            }
            foreach ($pares as [$a, $b]) {
                if ($a !== $b) {
                    $unir((int) $a, (int) $b);
                }
            }

            // Grupo = menor id do componente; componentes de um só anúncio ficam sem grupo.
            $componentes = [];
            foreach (array_keys($pai) as $id) {
                $componentes[$achar($id)][] = $id;
            }

            $mudou = 0;
            $atual = $membros->pluck('grupo_id', 'id');
            foreach ($componentes as $ids) {
                $grupo = count($ids) > 1 ? min($ids) : null;
                foreach ($ids as $id) {
                    if (($atual[$id] ?? null) !== $grupo) {
                        Anuncio::whereKey($id)->update(['grupo_id' => $grupo]);
                        $mudou++;
                    }
                }
            }

            return $mudou;
        });
    }
}
