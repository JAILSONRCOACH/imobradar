<?php

namespace App\Services;

use App\Models\Busca;
use App\Models\Cidade;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Decide se uma pesquisa precisa disparar uma busca nova na web (via n8n)
 * e dispara, respeitando validade e limites de custo.
 */
class BuscaSobDemanda
{
    public function ativa(): bool
    {
        return (string) config('imobradar.busca.n8n_webhook') !== '';
    }

    /**
     * Devolve a busca em andamento ou a que acabou de ser disparada; null se os dados ainda estão válidos
     * ou se não foi possível disparar (limite atingido, n8n fora do ar).
     *
     * @return array{busca: ?Busca, motivo: ?string}
     */
    public function garantir(Cidade $cidade, string $finalidade, ?User $user): array
    {
        if (! $this->ativa()) {
            return ['busca' => null, 'motivo' => null];
        }

        $ultima = Busca::where('cidade_id', $cidade->id)->where('finalidade', $finalidade)->latest('id')->first();

        if ($ultima?->emAndamento()) {
            return ['busca' => $ultima, 'motivo' => null];
        }
        if ($ultima && $ultima->status === 'buscando') {
            $ultima->update(['status' => 'falhou', 'erro' => 'Sem resposta do n8n no tempo limite.']);
        }

        $validade = now()->subHours((int) config('imobradar.busca.horas_validade'));
        if ($ultima && $ultima->status === 'concluida' && $ultima->concluida_em?->gt($validade)) {
            return ['busca' => null, 'motivo' => null];
        }
        // Falha recente: espera 15 minutos antes de tentar de novo, para não gastar em loop.
        if ($ultima && $ultima->status === 'falhou' && $ultima->updated_at->gt(now()->subMinutes(15))) {
            return ['busca' => null, 'motivo' => null];
        }

        if ($motivo = $this->limiteAtingido($user)) {
            return ['busca' => null, 'motivo' => $motivo];
        }

        $busca = Busca::create([
            'cidade_id' => $cidade->id,
            'finalidade' => $finalidade,
            'status' => 'buscando',
            'user_id' => $user?->id,
        ]);

        try {
            Http::timeout(8)->connectTimeout(4)
                ->withHeaders(['X-Imobradar-Segredo' => (string) config('imobradar.busca.n8n_segredo')])
                ->post((string) config('imobradar.busca.n8n_webhook'), [
                    'busca_id' => $busca->id,
                    'cidade' => $cidade->nome,
                    'cidade_ibge' => $cidade->ibge,
                    'uf' => 'PB',
                    'finalidade' => $finalidade,
                    'api_base' => rtrim((string) config('app.url'), '/').'/api',
                ])
                ->throw();
        } catch (Throwable $e) {
            Log::error("Busca {$busca->id}: falha ao chamar o n8n: {$e->getMessage()}");
            $busca->update(['status' => 'falhou', 'erro' => 'Falha ao chamar o n8n: '.$e->getMessage()]);

            return ['busca' => null, 'motivo' => 'A busca automática está indisponível agora. Tente de novo em alguns minutos.'];
        }

        return ['busca' => $busca, 'motivo' => null];
    }

    private function limiteAtingido(?User $user): ?string
    {
        if ($user?->is_admin) {
            return null;
        }
        $hoje = now()->startOfDay();
        if ($user && Busca::where('user_id', $user->id)->where('created_at', '>=', $hoje)->count() >= (int) config('imobradar.busca.limite_usuario_dia')) {
            return 'Você atingiu o limite de buscas novas de hoje. Os resultados já guardados continuam disponíveis.';
        }
        if (Busca::where('created_at', '>=', $hoje)->count() >= (int) config('imobradar.busca.limite_total_dia')) {
            return 'O limite diário de buscas novas foi atingido. Os resultados já guardados continuam disponíveis.';
        }

        return null;
    }

    /** Chamado quando o n8n finaliza a coleta ligada à busca. */
    public function concluir(Busca $busca, int $encontrados, int $novos, ?string $erro): void
    {
        $busca->update([
            'status' => $erro ? 'falhou' : 'concluida',
            'encontrados' => $encontrados,
            'novos' => $novos,
            'erro' => $erro,
            'concluida_em' => now(),
        ]);
    }
}
