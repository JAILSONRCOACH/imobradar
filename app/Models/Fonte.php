<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fonte extends Model
{
    protected $table = 'fontes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['ativa' => 'boolean'];
    }

    public function anuncios(): HasMany
    {
        return $this->hasMany(Anuncio::class);
    }
}
