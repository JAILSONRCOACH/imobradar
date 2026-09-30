<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Anuncio;
use Illuminate\View\View;

class AnuncioController extends Controller
{
    public function show(Anuncio $anuncio): View
    {
        $anuncio->load(['cidade', 'bairro', 'fonte', 'eventos']);
        abort_unless($anuncio->fonte->ativa, 404);

        $duplicados = $anuncio->grupo_id
            ? Anuncio::with('fonte:id,nome')->where('grupo_id', $anuncio->grupo_id)
                ->whereHas('fonte', fn ($q) => $q->where('ativa', true))->whereKeyNot($anuncio->id)->orderBy('preco')->get()
            : collect();

        // Referência de mercado: mediana de R$/m² dos anúncios ativos semelhantes (mesma cidade, tipo e finalidade).
        $mediana = null;
        $amostra = 0;
        if ($anuncio->preco_m2) {
            $valores = Anuncio::where('status', 'ativo')
                ->where('cidade_id', $anuncio->cidade_id)
                ->where('tipo', $anuncio->tipo)
                ->where('finalidade', 'venda')
                ->whereNotNull('preco_m2')
                ->pluck('preco_m2')->sort()->values();
            $amostra = $valores->count();
            if ($amostra >= 5) {
                $meio = intdiv($amostra, 2);
                $mediana = $amostra % 2 ? $valores[$meio] : ($valores[$meio - 1] + $valores[$meio]) / 2;
            }
        }

        return view('anuncio.show', compact('anuncio', 'duplicados', 'mediana', 'amostra'));
    }
}
