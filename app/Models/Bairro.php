<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bairro extends Model
{
    protected $table = 'bairros';

    protected $guarded = ['id'];

    public function cidade(): BelongsTo
    {
        return $this->belongsTo(Cidade::class);
    }
}
