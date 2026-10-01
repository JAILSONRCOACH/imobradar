# Rotinas semanais do IMOBRADAR

Crie uma rotina no Claude Code (claude.ai/code → Rotinas → Nova) para cada cidade.
Repositório: **JAILSONRCOACH/imobradar**. Recorrência: semanal, segunda-feira cedo.
Copie o texto inteiro do bloco da cidade no campo de instruções.

## João Pessoa

```
Você atualiza a base de anúncios de imóveis de João Pessoa/PB do IMOBRADAR.

1. No repositório JAILSONRCOACH/imobradar, trabalhe no ramo "dados":
   git fetch origin dados && git checkout dados && git pull origin dados
   Se o arquivo joao-pessoa.json existir, leia: ele é a base da semana anterior.

2. Pesquise anúncios ATIVOS de imóveis no município de João Pessoa/PB, nas três finalidades:
   venda, aluguel mensal e temporada.
   Bairros e praias para cobrir: Cabo Branco, Tambaú, Manaíra, Bessa, Altiplano, Bancários, Mangabeira, Centro e Jardim Oceania.
   Fontes: OLX, ZAP Imóveis, VivaReal, Chaves na Mão, Imovelweb, MGF Imóveis e sites de
   imobiliárias e corretores da região. Abra as páginas de listagem e os anúncios para pegar
   os dados reais; não fique só no resumo da busca.

3. Regras:
   - Só anúncios individuais: a URL abre UM imóvel, nunca uma página de listagem.
   - Só imóveis localizados no município de João Pessoa.
   - Não invente nada. Campo que não aparece no anúncio fica null.
   - preco em reais, só número. Aluguel: valor mensal com "preco_unidade": "mes".
     Temporada: valor da diária com "preco_unidade": "diaria".
   - fonte = nome do site onde o anúncio está.
   - Não repita a mesma URL.
   - Anúncios da semana anterior que continuam no ar entram de novo; os que saíram, não.
   - Não use Airbnb nem Vrbo.

4. Grave o arquivo joao-pessoa.json na raiz do ramo "dados" exatamente neste formato:
   {"cidade_ibge": 2507507, "gerado_em": "AAAA-MM-DD", "anuncios": [
     {"url": "", "titulo": "", "fonte": "", "finalidade": "venda|aluguel|temporada",
      "tipo": "casa|casa_condominio|apartamento|cobertura|flat|terreno|chacara_sitio|comercial|outro",
      "preco": null, "preco_unidade": null, "bairro": null, "quartos": null, "suites": null,
      "banheiros": null, "vagas": null, "area_construida": null, "area_terreno": null,
      "valor_condominio": null, "valor_iptu": null, "piscina": null, "proximo_praia": null,
      "descricao": null}
   ]}
   Valide o JSON antes de salvar (python3 -m json.tool joao-pessoa.json).

5. Commit com a mensagem "Atualiza João Pessoa AAAA-MM-DD" e push no ramo dados:
   git add joao-pessoa.json && git commit -m "..." && git push origin dados

6. No final, informe quantos anúncios foram gravados por finalidade e quais fontes funcionaram.
```

## Cabedelo

```
Você atualiza a base de anúncios de imóveis de Cabedelo/PB do IMOBRADAR.

1. No repositório JAILSONRCOACH/imobradar, trabalhe no ramo "dados":
   git fetch origin dados && git checkout dados && git pull origin dados
   Se o arquivo cabedelo.json existir, leia: ele é a base da semana anterior.

2. Pesquise anúncios ATIVOS de imóveis no município de Cabedelo/PB, nas três finalidades:
   venda, aluguel mensal e temporada.
   Bairros e praias para cobrir: Intermares, Camboinha, Ponta de Campina, Poço, Formosa e Centro.
   Fontes: OLX, ZAP Imóveis, VivaReal, Chaves na Mão, Imovelweb, MGF Imóveis e sites de
   imobiliárias e corretores da região. Abra as páginas de listagem e os anúncios para pegar
   os dados reais; não fique só no resumo da busca.

3. Regras:
   - Só anúncios individuais: a URL abre UM imóvel, nunca uma página de listagem.
   - Só imóveis localizados no município de Cabedelo.
   - Não invente nada. Campo que não aparece no anúncio fica null.
   - preco em reais, só número. Aluguel: valor mensal com "preco_unidade": "mes".
     Temporada: valor da diária com "preco_unidade": "diaria".
   - fonte = nome do site onde o anúncio está.
   - Não repita a mesma URL.
   - Anúncios da semana anterior que continuam no ar entram de novo; os que saíram, não.
   - Não use Airbnb nem Vrbo.

4. Grave o arquivo cabedelo.json na raiz do ramo "dados" exatamente neste formato:
   {"cidade_ibge": 2503209, "gerado_em": "AAAA-MM-DD", "anuncios": [
     {"url": "", "titulo": "", "fonte": "", "finalidade": "venda|aluguel|temporada",
      "tipo": "casa|casa_condominio|apartamento|cobertura|flat|terreno|chacara_sitio|comercial|outro",
      "preco": null, "preco_unidade": null, "bairro": null, "quartos": null, "suites": null,
      "banheiros": null, "vagas": null, "area_construida": null, "area_terreno": null,
      "valor_condominio": null, "valor_iptu": null, "piscina": null, "proximo_praia": null,
      "descricao": null}
   ]}
   Valide o JSON antes de salvar (python3 -m json.tool cabedelo.json).

5. Commit com a mensagem "Atualiza Cabedelo AAAA-MM-DD" e push no ramo dados:
   git add cabedelo.json && git commit -m "..." && git push origin dados

6. No final, informe quantos anúncios foram gravados por finalidade e quais fontes funcionaram.
```

## Conde

```
Você atualiza a base de anúncios de imóveis de Conde/PB do IMOBRADAR.

1. No repositório JAILSONRCOACH/imobradar, trabalhe no ramo "dados":
   git fetch origin dados && git checkout dados && git pull origin dados
   Se o arquivo conde.json existir, leia: ele é a base da semana anterior.

2. Pesquise anúncios ATIVOS de imóveis no município de Conde/PB, nas três finalidades:
   venda, aluguel mensal e temporada.
   Bairros e praias para cobrir: Jacumã, Carapibus, Tabatinga, Coqueirinho, Tambaba, Gurugi e Centro (Jacumã é a praia principal: dê prioridade).
   Fontes: OLX, ZAP Imóveis, VivaReal, Chaves na Mão, Imovelweb, MGF Imóveis e sites de
   imobiliárias e corretores da região. Abra as páginas de listagem e os anúncios para pegar
   os dados reais; não fique só no resumo da busca.

3. Regras:
   - Só anúncios individuais: a URL abre UM imóvel, nunca uma página de listagem.
   - Só imóveis localizados no município de Conde.
   - Não invente nada. Campo que não aparece no anúncio fica null.
   - preco em reais, só número. Aluguel: valor mensal com "preco_unidade": "mes".
     Temporada: valor da diária com "preco_unidade": "diaria".
   - fonte = nome do site onde o anúncio está.
   - Não repita a mesma URL.
   - Anúncios da semana anterior que continuam no ar entram de novo; os que saíram, não.
   - Não use Airbnb nem Vrbo.

4. Grave o arquivo conde.json na raiz do ramo "dados" exatamente neste formato:
   {"cidade_ibge": 2504603, "gerado_em": "AAAA-MM-DD", "anuncios": [
     {"url": "", "titulo": "", "fonte": "", "finalidade": "venda|aluguel|temporada",
      "tipo": "casa|casa_condominio|apartamento|cobertura|flat|terreno|chacara_sitio|comercial|outro",
      "preco": null, "preco_unidade": null, "bairro": null, "quartos": null, "suites": null,
      "banheiros": null, "vagas": null, "area_construida": null, "area_terreno": null,
      "valor_condominio": null, "valor_iptu": null, "piscina": null, "proximo_praia": null,
      "descricao": null}
   ]}
   Valide o JSON antes de salvar (python3 -m json.tool conde.json).

5. Commit com a mensagem "Atualiza Conde AAAA-MM-DD" e push no ramo dados:
   git add conde.json && git commit -m "..." && git push origin dados

6. No final, informe quantos anúncios foram gravados por finalidade e quais fontes funcionaram.
```
