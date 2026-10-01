# IMOBRADAR

Busca e monitoramento de imóveis na Paraíba. Reúne anúncios de portais, imobiliárias e
classificados, agrupa repetidos, registra histórico de preço e mostra quando um anúncio sai do ar.

**Pesquise menos. Encontre mais.**

## Arquitetura

| Parte | Onde roda | O que faz |
|---|---|---|
| Site + API de ingestão (este repositório) | Hostinger Business (PHP 8.3 + MySQL) | Busca, filtros, ficha do imóvel, histórico; recebe os dados dos coletores |
| Coletores | VPS | Varre as fontes, extrai e normaliza, envia em lotes para a API |

O site nunca raspa nada: só recebe dados pela API, protegida por token.

## Estrutura

- `app/Services/Ingestor.php`: grava anúncios, detecta mudança de preço, "saiu do ar" e "voltou".
- `app/Services/Agrupador.php`: agrupa prováveis duplicados (mesmo imóvel em fontes diferentes).
- `app/Support/Normalizador.php`: padroniza tipo, finalidade, preços e números em texto.
- `app/Console/Commands/ImportarLucena.php`: importa a base do monitoramento de Lucena.
- `database/data/municipios_pb.csv`: os 223 municípios da PB (IBGE).

### Tabelas

`cidades`, `bairros`, `fontes`, `coletas`, `anuncios`, `anuncio_eventos` (novo / preco / removido / voltou).

## API de ingestão

Todas as chamadas exigem `Authorization: Bearer <IMOBRADAR_INGEST_TOKEN>`.

1. **Abrir coleta**: `POST /api/coletas`
   ```json
   {"fonte": "olx", "fonte_nome": "OLX", "cidade_ibge": 2508604, "finalidade": "venda"}
   ```
   Resposta: `{"coleta_id": 12, ...}`. Uma coleta = uma fonte, opcionalmente limitada a uma cidade e finalidade.

2. **Enviar lotes** (até 500 por chamada): `POST /api/coletas/12/anuncios`
   ```json
   {"anuncios": [{
     "id_externo": "1539986980", "url": "https://...", "titulo": "Casa 3 quartos",
     "finalidade": "venda", "tipo": "casa", "preco": "R$ 320.000",
     "cidade_ibge": 2508604, "bairro": "Fagundes",
     "area_construida": 150, "area_terreno": 360, "quartos": 3, "suites": 1, "banheiros": 2, "vagas": 2,
     "valor_condominio": null, "valor_iptu": null, "piscina": true, "proximo_praia": true,
     "descricao": "...", "foto_url": "https://...", "caracteristicas": ["varanda", "churrasqueira"]
   }]}
   ```
   Obrigatórios: `id_externo`, `url`, `titulo`, `finalidade` (ou definida na coleta) e a cidade
   (`cidade_ibge` / `cidade`, ou definida na coleta). Itens inválidos voltam em `rejeitados` com o motivo.

3. **Finalizar**: `POST /api/coletas/12/finalizar`
   ```json
   {"completa": true}
   ```
   `completa: true` significa que o escopo inteiro foi varrido. Só nesse caso os anúncios que não
   apareceram são marcados como removidos. Coleta parcial ou com `erro` nunca remove nada.

## Instalação no servidor (Hostinger)

```bash
cd ~/domains/imobradar.com.br
git clone https://github.com/JAILSONRCOACH/imobradar.git app
cd app
composer install --no-dev --optimize-autoloader
cp .env.example .env        # preencher DB_PASSWORD e IMOBRADAR_INGEST_TOKEN
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan imobradar:importar-lucena
# public_html passa a apontar para app/public
cd .. && mv public_html public_html_antigo && ln -s app/public public_html
```

Atualizações: `bash deploy.sh` dentro de `app/`.

## Testes

```bash
composer install
php artisan test
```

## Atualização semanal

Rotinas do Claude Code (ver `rotinas/ROTINAS-SEMANAIS.md`) gravam um JSON por cidade no ramo `dados`.
O cron da Hostinger roda `php artisan imobradar:importar-semanal` toda segunda, importa as cidades e
marca como fora do ar o que não aparece há mais de 21 dias. Lucena continua com a rotina diária própria
(`imobradar:importar-lucena`).
