{{-- Campo de cidade com sugestões dos 223 municípios. Sem JavaScript, o servidor entende o nome digitado. --}}
<div class="campo-cidade" data-autocompletar>
    <label for="{{ $id }}" class="{{ $rotuloVisivel ?? false ? 'rotulo' : 'sr' }}">Cidade</label>
    <input id="{{ $id }}" name="cidade" type="text" autocomplete="off" spellcheck="false"
           role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="{{ $id }}-lista"
           placeholder="{{ $placeholder ?? 'Digite a cidade: João Pessoa, Cabedelo, Conde…' }}"
           value="{{ $valor ?? '' }}">
    <ul id="{{ $id }}-lista" class="sugestoes" role="listbox" hidden></ul>
</div>
