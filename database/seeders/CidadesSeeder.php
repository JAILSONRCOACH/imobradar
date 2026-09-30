<?php

namespace Database\Seeders;

use App\Models\Cidade;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Os 223 municípios da Paraíba (código IBGE, nome e coordenadas da sede). */
class CidadesSeeder extends Seeder
{
    public function run(): void
    {
        $arquivo = database_path('data/municipios_pb.csv');
        $h = fopen($arquivo, 'r');
        $cab = fgetcsv($h);

        while (($linha = fgetcsv($h)) !== false) {
            $r = array_combine($cab, $linha);
            Cidade::updateOrCreate(
                ['ibge' => (int) $r['ibge']],
                [
                    'nome' => $r['nome'],
                    'slug' => Str::slug($r['nome']),
                    'uf' => 'PB',
                    'lat' => (float) $r['latitude'],
                    'lng' => (float) $r['longitude'],
                    'capital' => $r['capital'] === '1',
                ],
            );
        }
        fclose($h);
    }
}
