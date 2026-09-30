<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Busca extends Model
{
    protected $table = 'buscas';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['concluida_em' => 'datetime'];
    }

    public function cidade(): BelongsTo
    {
        return $this->belongsTo(Cidade::class);
    }

    public function emAndamento(): bool
    {
        return $this->status === 'buscando'
            && $this->created_at->gt(now()->subMinutes((int) config('imobradar.busca.minutos_limite')));
    }
}
