{{-- Fundo mostrado enquanto não há foto (ou se a imagem do site de origem não carregar). --}}
<span class="foto-vazia" aria-hidden="true">
    @if (in_array($tipo, ['terreno', 'chacara_sitio', 'fazenda'], true))
        <svg viewBox="0 0 64 64"><path d="M6 46l14-10 12 6 12-12 14 10v14H6z" /><path d="M6 46h52" /><circle cx="46" cy="18" r="5" /></svg>
    @elseif (in_array($tipo, ['apartamento', 'cobertura', 'flat', 'kitnet', 'sala_comercial'], true))
        <svg viewBox="0 0 64 64"><rect x="16" y="8" width="32" height="50" rx="2" /><path d="M24 18h4M36 18h4M24 28h4M36 28h4M24 38h4M36 38h4M28 58v-8h8v8" /></svg>
    @else
        <svg viewBox="0 0 64 64"><path d="M8 30L32 10l24 20" /><path d="M14 26v30h36V26" /><path d="M27 56V42h10v14" /></svg>
    @endif
</span>
