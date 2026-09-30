<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cidade extends Model
{
    protected $table = 'cidades';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['capital' => 'boolean', 'lat' => 'float', 'lng' => 'float'];
    }

    public function bairros(): HasMany
    {
        return $this->hasMany(Bairro::class);
    }

    public function anuncios(): HasMany
    {
        return $this->hasMany(Anuncio::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
