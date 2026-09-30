<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp', 20)->nullable()->after('email');
            // corretor | imobiliaria | investidor | comprador | outro
            $table->string('perfil', 20)->nullable()->after('whatsapp');
            $table->string('mensagem', 500)->nullable()->after('perfil');
            // pendente | aprovado | recusado
            $table->string('status', 10)->default('pendente')->after('mensagem');
            $table->boolean('is_admin')->default(false)->after('status');
            $table->timestamp('aprovado_em')->nullable()->after('is_admin');
            $table->timestamp('ultimo_acesso_em')->nullable()->after('aprovado_em');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['whatsapp', 'perfil', 'mensagem', 'status', 'is_admin', 'aprovado_em', 'ultimo_acesso_em']);
        });
    }
};
