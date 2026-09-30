<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cidade;
use App\Models\Coleta;
use App\Models\Fonte;
use App\Services\Ingestor;
use App\Support\Normalizador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * API usada pelos coletores (VPS):
 *   POST /api/coletas                    abre uma coleta para fonte + cidade + finalidade
 *   POST /api/coletas/{coleta}/anuncios  envia um lote de anúncios (até 500)
 *   POST /api/coletas/{coleta}/finalizar fecha a coleta (completa=true marca os sumidos como removidos)
 */
class ColetaController extends Controller
{
    public function abrir(Request $request): JsonResponse
    {
        $v = $request->validate([
            'fonte' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9][a-z0-9-]*$/'],
            'fonte_nome' => ['nullable', 'string', 'max:120'],
            'fonte_url' => ['nullable', 'url', 'max:255'],
            'fonte_tipo' => ['nullable', Rule::in(['portal', 'imobiliaria', 'classificado', 'temporada', 'leilao', 'outro'])],
            'cidade_ibge' => ['nullable', 'integer'],
            'cidade' => ['nullable', 'string', 'max:120'],
            'finalidade' => ['nullable', Rule::in(Normalizador::FINALIDADES)],
        ]);

        $fonte = Fonte::firstOrCreate(
            ['slug' => $v['fonte']],
            [
                'nome' => $v['fonte_nome'] ?? Str::headline($v['fonte']),
                'site_url' => $v['fonte_url'] ?? null,
                'tipo' => $v['fonte_tipo'] ?? 'portal',
            ],
        );
        if (! $fonte->ativa) {
            return response()->json(['erro' => "Fonte '{$fonte->slug}' está desativada."], 409);
        }

        $cidadeId = null;
        if (! empty($v['cidade_ibge'])) {
            $cidadeId = Cidade::where('ibge', $v['cidade_ibge'])->value('id');
        } elseif (! empty($v['cidade'])) {
            $cidadeId = Cidade::where('slug', Str::slug($v['cidade']))->value('id');
        }
        if ((! empty($v['cidade_ibge']) || ! empty($v['cidade'])) && ! $cidadeId) {
            return response()->json(['erro' => 'Cidade não encontrada na Paraíba.'], 422);
        }

        $coleta = Coleta::create([
            'fonte_id' => $fonte->id,
            'cidade_id' => $cidadeId,
            'finalidade' => $v['finalidade'] ?? null,
            'status' => 'em_andamento',
            'iniciada_em' => now(),
        ]);

        return response()->json(['coleta_id' => $coleta->id, 'fonte_id' => $fonte->id, 'cidade_id' => $cidadeId], 201);
    }

    public function anuncios(Request $request, Coleta $coleta, Ingestor $ingestor): JsonResponse
    {
        if (! $coleta->emAndamento()) {
            return response()->json(['erro' => 'Coleta já finalizada.'], 409);
        }

        $max = (int) config('imobradar.ingest_lote_max');
        $v = $request->validate([
            'anuncios' => ['required', 'array', 'min:1', "max:{$max}"],
            'anuncios.*' => ['array'],
        ]);

        $res = $ingestor->lote($coleta, $v['anuncios']);

        return response()->json($res + ['coleta_id' => $coleta->id]);
    }

    public function finalizar(Request $request, Coleta $coleta, Ingestor $ingestor): JsonResponse
    {
        if (! $coleta->emAndamento()) {
            return response()->json(['erro' => 'Coleta já finalizada.'], 409);
        }

        $v = $request->validate([
            'completa' => ['required', 'boolean'],
            'erro' => ['nullable', 'string', 'max:5000'],
        ]);

        $coleta = $ingestor->finalizar($coleta, (bool) $v['completa'], $v['erro'] ?? null);

        return response()->json($coleta->only([
            'id', 'status', 'recebidos', 'novos', 'alterados', 'voltaram', 'removidos', 'rejeitados', 'iniciada_em', 'finalizada_em',
        ]));
    }
}
