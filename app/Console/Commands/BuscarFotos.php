<?php

namespace App\Console\Commands;

use App\Models\Anuncio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Busca a foto principal de cada anúncio no próprio anúncio original (og:image / twitter:image / JSON-LD).
 * Só guarda o endereço da imagem; o arquivo continua no site de origem.
 */
class BuscarFotos extends Command
{
    protected $signature = 'imobradar:buscar-fotos
        {--limite=200 : Máximo de anúncios por execução}
        {--pausa=1 : Segundos entre requisições}';

    protected $description = 'Preenche a foto principal dos anúncios a partir do anúncio original';

    public function handle(): int
    {
        $anuncios = Anuncio::query()
            ->where('status', 'ativo')
            ->whereNull('foto_url')
            ->whereHas('fonte', fn ($q) => $q->where('ativa', true))
            ->orderByDesc('primeira_vez_em')
            ->limit((int) $this->option('limite'))
            ->get(['id', 'url', 'grupo_id']);

        if ($anuncios->isEmpty()) {
            $this->info('Nenhum anúncio sem foto.');

            return self::SUCCESS;
        }

        $ok = 0;
        $falhas = [];
        $barra = $this->output->createProgressBar($anuncios->count());

        foreach ($anuncios as $a) {
            $barra->advance();
            $host = parse_url($a->url, PHP_URL_HOST) ?: '?';
            try {
                $resp = Http::timeout(12)->connectTimeout(6)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; IMOBRADAR/1.0; +https://imobradar.com.br)',
                        'Accept-Language' => 'pt-BR,pt;q=0.9',
                    ])
                    ->get($a->url);

                $foto = $resp->successful() ? $this->extrair($resp->body(), $a->url) : null;
                if ($foto) {
                    $a->update(['foto_url' => $foto]);
                    // Repetidos do mesmo imóvel sem foto herdam a imagem.
                    if ($a->grupo_id) {
                        Anuncio::where('grupo_id', $a->grupo_id)->whereNull('foto_url')->update(['foto_url' => $foto]);
                    }
                    $ok++;
                } else {
                    $falhas[$host] = ($falhas[$host] ?? 0) + 1;
                }
            } catch (Throwable) {
                $falhas[$host] = ($falhas[$host] ?? 0) + 1;
            }
            usleep((int) ((float) $this->option('pausa') * 1_000_000));
        }

        $barra->finish();
        $this->newLine(2);
        $this->info("Fotos encontradas: {$ok} de {$anuncios->count()}");
        if ($falhas) {
            arsort($falhas);
            $this->table(['site sem foto (bloqueou ou não tem)', 'anúncios'], collect($falhas)->map(fn ($n, $h) => [$h, $n])->values()->all());
        }

        return self::SUCCESS;
    }

    private function extrair(string $html, string $base): ?string
    {
        $html = substr($html, 0, 800_000);
        $padroes = [
            '/<meta[^>]+property=["\']og:image(?::secure_url)?["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i',
            '/<meta[^>]+name=["\']twitter:image(?::src)?["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/"image"\s*:\s*\[?\s*"(https?:[^"]+)"/i',
        ];
        foreach ($padroes as $p) {
            if (preg_match($p, $html, $m)) {
                $url = html_entity_decode(str_replace('\/', '/', trim($m[1])));
                if (str_starts_with($url, '//')) {
                    $url = 'https:'.$url;
                } elseif (str_starts_with($url, '/')) {
                    $p = parse_url($base);
                    $url = ($p['scheme'] ?? 'https').'://'.($p['host'] ?? '').$url;
                }
                // Ignora logotipos e imagens genéricas do portal.
                if (preg_match('#^https?://#i', $url) && ! preg_match('/logo|default|placeholder|share-image|og-image\.(png|jpg)/i', $url)) {
                    return mb_substr($url, 0, 1000);
                }
            }
        }

        return null;
    }
}
