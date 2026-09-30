<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Anuncio extends Model
{
    protected $table = 'anuncios';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'outros_links' => 'array',
            'caracteristicas' => 'array',
            'piscina' => 'boolean',
            'proximo_praia' => 'boolean',
            'preco' => 'float',
            'valor_condominio' => 'float',
            'valor_iptu' => 'float',
            'area_construida' => 'float',
            'area_terreno' => 'float',
            'preco_m2' => 'float',
            'lat' => 'float',
            'lng' => 'float',
            'primeira_vez_em' => 'date',
            'ultima_vez_em' => 'date',
            'removido_em' => 'date',
        ];
    }

    public function fonte(): BelongsTo
    {
        return $this->belongsTo(Fonte::class);
    }

    public function cidade(): BelongsTo
    {
        return $this->belongsTo(Cidade::class);
    }

    public function bairro(): BelongsTo
    {
        return $this->belongsTo(Bairro::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(AnuncioEvento::class)->orderBy('ocorrido_em')->orderBy('id');
    }

    /** Outros anúncios do mesmo grupo de prováveis duplicados. */
    public function duplicados(): HasMany
    {
        return $this->hasMany(Anuncio::class, 'grupo_id', 'grupo_id')->whereKeyNot($this->getKey());
    }

    public function scopeAtivos(Builder $q): Builder
    {
        return $q->where('status', 'ativo');
    }

    /** Área usada no cálculo de R$/m²: terreno para lotes, construída para o resto. */
    public function areaReferencia(): ?float
    {
        if (in_array($this->tipo, ['terreno', 'chacara_sitio', 'fazenda'], true)) {
            return $this->area_terreno ?: $this->area_construida;
        }

        return $this->area_construida ?: null;
    }

    public function isNovo(): bool
    {
        return $this->primeira_vez_em
            && $this->primeira_vez_em->gte(now()->subDays((int) config('imobradar.dias_novo'))->startOfDay());
    }
}
