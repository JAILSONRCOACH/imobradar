// Botão de mostrar/ocultar senha em todos os campos de senha.
(function () {
  var olho = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>';
  var fechado = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M3 3l18 18M10.6 5.1A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.2M6.6 6.6C3.8 8.4 2 12 2 12s3.6 7 10 7a10 10 0 0 0 4.4-1M9.9 9.9a3 3 0 0 0 4.2 4.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
  document.querySelectorAll('input[type="password"]').forEach(function (input) {
    var caixa = document.createElement('span');
    caixa.className = 'campo-senha';
    input.parentNode.insertBefore(caixa, input);
    caixa.appendChild(input);
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'ver-senha';
    b.setAttribute('aria-label', 'Mostrar senha');
    b.setAttribute('aria-pressed', 'false');
    b.innerHTML = olho;
    b.addEventListener('click', function () {
      var mostrar = input.type === 'password';
      input.type = mostrar ? 'text' : 'password';
      b.innerHTML = mostrar ? fechado : olho;
      b.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
      b.setAttribute('aria-pressed', String(mostrar));
      input.focus();
    });
    caixa.appendChild(b);
  });
})();
