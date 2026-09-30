// Acompanha a busca sob demanda e recarrega a página quando termina.
(function () {
  var caixa = document.querySelector('[data-busca]');
  if (!caixa) return;
  var texto = caixa.querySelector('[data-busca-texto]');
  var url = caixa.getAttribute('data-busca');
  var tentativas = 0;

  function consultar() {
    tentativas++;
    fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) {
        if (!d) return agendar();
        if (d.status === 'buscando') {
          if (d.encontrados > 0) texto.textContent = d.encontrados + ' anúncios recebidos até agora…';
          return agendar();
        }
        if (d.status === 'concluida') {
          texto.textContent = 'Pronto: ' + d.encontrados + ' anúncios encontrados. Atualizando…';
        } else {
          texto.textContent = 'A busca não terminou. Mostrando o que já temos.';
        }
        setTimeout(function () { location.reload(); }, 1200);
      })
      .catch(agendar);
  }
  function agendar() { if (tentativas < 120) setTimeout(consultar, 4000); }
  setTimeout(consultar, 3000);
})();
