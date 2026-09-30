@if ($paginator->hasPages())
<nav class="paginacao" aria-label="Paginação">
    @if ($paginator->onFirstPage())
        <span class="pg desativado">‹ Anterior</span>
    @else
        <a class="pg" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Anterior</a>
    @endif
    <span class="pg-info">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>
    @if ($paginator->hasMorePages())
        <a class="pg" href="{{ $paginator->nextPageUrl() }}" rel="next">Próxima ›</a>
    @else
        <span class="pg desativado">Próxima ›</span>
    @endif
</nav>
@endif
