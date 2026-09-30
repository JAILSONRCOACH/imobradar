<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coleta extends Model
{
    protected $table = 'coletas';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['iniciada_em' => 'datetime', 'finalizada_em' => 'datetime'];
    }

    public function fonte(): BelongsTo
    {
        return $this->belongsTo(Fonte::class);
    }

    public function cidade(): BelongsTo
    {
        return $this->belongsTo(Cidade::class);
    }

    public function emAndamento(): bool
    {
        return $this->status === 'em_andamento';
    }
}
