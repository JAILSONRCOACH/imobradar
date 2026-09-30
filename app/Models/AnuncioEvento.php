<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnuncioEvento extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'anuncio_eventos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'ocorrido_em' => 'date',
            'preco_anterior' => 'float',
            'preco_novo' => 'float',
        ];
    }

    public function anuncio(): BelongsTo
    {
        return $this->belongsTo(Anuncio::class);
    }
}
