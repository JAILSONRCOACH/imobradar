<?php

namespace Tests\Feature;

use App\Mail\AcessoLiberado;
use App\Mail\NovoCadastro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AcessoTest extends TestCase
{
    use RefreshDatabase;

    private function dadosCadastro(array $extra = []): array
    {
        return $extra + [
            'name' => 'Maria da Silva', 'email' => 'maria@exemplo.com', 'whatsapp' => '(83) 98888-7777',
            'perfil' => 'corretor', 'mensagem' => 'Casas em Lucena', 'password' => 'senha1234', 'password_confirmation' => 'senha1234',
        ];
    }

    public function test_visitante_vai_para_entrar(): void
    {
        $this->get('/')->assertRedirect('/entrar');
        $this->get('/entrar')->assertOk()->assertSee('Pedir cadastro');
        $this->get('/cadastro')->assertOk();
    }

    public function test_cadastro_fica_pendente_e_avisa_admin(): void
    {
        Mail::fake();
        $this->post('/cadastro', $this->dadosCadastro())->assertRedirect('/aguardando');

        $u = User::where('email', 'maria@exemplo.com')->first();
        $this->assertSame('pendente', $u->status);
        $this->assertFalse($u->is_admin);
        Mail::assertSent(NovoCadastro::class, fn ($m) => $m->hasTo(config('imobradar.admin_email')));

        $this->get('/')->assertRedirect('/aguardando');
        $this->get('/aguardando')->assertOk()->assertSee('em análise');
    }

    public function test_campos_protegidos_nao_sao_aceitos_no_cadastro(): void
    {
        Mail::fake();
        $this->post('/cadastro', $this->dadosCadastro(['status' => 'aprovado', 'is_admin' => 1]));
        $u = User::where('email', 'maria@exemplo.com')->first();
        $this->assertSame('pendente', $u->status);
        $this->assertFalse($u->is_admin);
    }

    public function test_robo_que_preenche_armadilha_e_barrado(): void
    {
        $this->post('/cadastro', $this->dadosCadastro(['site' => 'http://spam']))->assertSessionHasErrors('site');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_link_do_email_aprova_so_no_post(): void
    {
        Mail::fake();
        $u = User::factory()->create(['status' => 'pendente']);
        $url = URL::temporarySignedRoute('aprovacao.mostrar', now()->addDay(), ['user' => $u->id]);

        $this->get($url)->assertOk()->assertSee('Liberar acesso');
        $this->assertSame('pendente', $u->fresh()->status);

        $this->post($url)->assertOk()->assertSee('Acesso liberado');
        $this->assertSame('aprovado', $u->fresh()->status);
        Mail::assertSent(AcessoLiberado::class, fn ($m) => $m->hasTo($u->email));
    }

    public function test_link_adulterado_e_recusado(): void
    {
        $u = User::factory()->create(['status' => 'pendente']);
        $outro = User::factory()->create(['status' => 'pendente']);
        $url = URL::temporarySignedRoute('aprovacao.mostrar', now()->addDay(), ['user' => $u->id]);

        $this->post(str_replace('/aprovar/'.$u->id, '/aprovar/'.$outro->id, $url))->assertForbidden();
        $this->post(route('aprovacao.aprovar', $u))->assertForbidden();
        $this->assertSame('pendente', $outro->fresh()->status);
    }

    public function test_painel_so_para_admin(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['status' => 'aprovado', 'is_admin' => true]);
        $comum = User::factory()->create(['status' => 'aprovado']);
        $pendente = User::factory()->create(['status' => 'pendente']);

        $this->actingAs($comum)->get('/admin/usuarios')->assertForbidden();
        $this->actingAs($comum)->post("/admin/usuarios/{$pendente->id}/aprovar")->assertForbidden();

        $this->actingAs($admin)->get('/admin/usuarios')->assertOk()->assertSee($pendente->email);
        $this->actingAs($admin)->post("/admin/usuarios/{$pendente->id}/aprovar")->assertRedirect();
        $this->assertSame('aprovado', $pendente->fresh()->status);

        $this->actingAs($admin)->post("/admin/usuarios/{$pendente->id}/recusar")->assertRedirect();
        $this->assertSame('recusado', $pendente->fresh()->status);
        $this->actingAs($pendente->fresh())->get('/')->assertRedirect('/aguardando');
    }

    public function test_entrar_e_limite_de_tentativas(): void
    {
        $u = User::factory()->create(['email' => 'joao@exemplo.com', 'password' => 'certa1234', 'status' => 'aprovado']);

        $this->post('/entrar', ['email' => 'joao@exemplo.com', 'password' => 'errada'])->assertSessionHasErrors('email');
        $this->post('/entrar', ['email' => 'joao@exemplo.com', 'password' => 'certa1234'])->assertRedirect('/');
        $this->assertAuthenticatedAs($u);
    }

    public function test_esqueci_a_senha_nao_revela_cadastro(): void
    {
        Mail::fake();
        $this->post('/esqueci-a-senha', ['email' => 'ninguem@exemplo.com'])->assertSessionHas('ok');
        User::factory()->create(['email' => 'ana@exemplo.com']);
        $this->post('/esqueci-a-senha', ['email' => 'ana@exemplo.com'])->assertSessionHas('ok');
        Mail::assertSent(\App\Mail\RedefinirSenha::class, 1);
    }
}
