<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Airbnb e Vrbo ficam fora do site: anúncios em inglês e sem preço sem datas. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('fontes')->whereIn('slug', ['airbnb', 'vrbo'])->update(['ativa' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('fontes')->whereIn('slug', ['airbnb', 'vrbo'])->update(['ativa' => true, 'updated_at' => now()]);
    }
};
