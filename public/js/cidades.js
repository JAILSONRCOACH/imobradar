// Sugestões de cidade (223 municípios da PB), sem dependências.
// Sem JavaScript o formulário continua funcionando: o servidor entende o nome digitado.
(function () {
  var fonte = document.getElementById('lista-cidades');
  if (!fonte) return;
  var cidades;
  try { cidades = JSON.parse(fonte.textContent); } catch (e) { return; }

  function normalizar(s) {
    return s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
  }
  cidades.forEach(function (c) { c.k = normalizar(c.n); });

  document.querySelectorAll('[data-autocompletar]').forEach(function (caixa) {
    var input = caixa.querySelector('input');
    var lista = caixa.querySelector('.sugestoes');
    var itens = [];
    var ativo = -1;

    function fechar() {
      lista.hidden = true;
      input.setAttribute('aria-expanded', 'false');
      input.removeAttribute('aria-activedescendant');
      ativo = -1;
    }

    function marcar(i) {
      var nos = lista.children;
      if (ativo >= 0 && nos[ativo]) nos[ativo].setAttribute('aria-selected', 'false');
      ativo = i;
      if (ativo >= 0 && nos[ativo]) {
        nos[ativo].setAttribute('aria-selected', 'true');
        input.setAttribute('aria-activedescendant', nos[ativo].id);
        nos[ativo].scrollIntoView({ block: 'nearest' });
      }
    }

    function escolher(c) {
      input.value = c.n;
      fechar();
      input.form.submit();
    }

    function montar() {
      var q = normalizar(input.value);
      if (!q) { fechar(); return; }
      var comeca = [], contem = [];
      cidades.forEach(function (c) {
        if (c.k.indexOf(q) === 0) comeca.push(c);
        else if (c.k.indexOf(q) > 0) contem.push(c);
      });
      itens = comeca.concat(contem).slice(0, 8);
      lista.innerHTML = '';
      if (!itens.length) { fechar(); return; }
      itens.forEach(function (c, i) {
        var li = document.createElement('li');
        li.id = input.id + '-op-' + i;
        li.setAttribute('role', 'option');
        li.setAttribute('aria-selected', 'false');
        var nome = document.createElement('span');
        nome.textContent = c.n;
        var qtd = document.createElement('small');
        qtd.textContent = c.c ? c.c + (c.c === 1 ? ' anúncio' : ' anúncios') : 'sem anúncios ainda';
        li.appendChild(nome);
        li.appendChild(qtd);
        li.addEventListener('mousedown', function (e) { e.preventDefault(); escolher(c); });
        lista.appendChild(li);
      });
      lista.hidden = false;
      input.setAttribute('aria-expanded', 'true');
      ativo = -1;
    }

    input.addEventListener('input', montar);
    input.addEventListener('focus', function () { if (input.value) montar(); });
    input.addEventListener('blur', function () { setTimeout(fechar, 100); });
    input.addEventListener('keydown', function (e) {
      if (lista.hidden) return;
      if (e.key === 'ArrowDown') { e.preventDefault(); marcar(Math.min(ativo + 1, itens.length - 1)); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); marcar(Math.max(ativo - 1, 0)); }
      else if (e.key === 'Enter' && ativo >= 0) { e.preventDefault(); escolher(itens[ativo]); }
      else if (e.key === 'Escape') { fechar(); }
    });
  });
})();
