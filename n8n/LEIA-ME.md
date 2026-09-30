# Fluxo n8n: busca sob demanda

Quando alguém pesquisa uma cidade que não foi buscada nas últimas 12 horas, o site chama
o webhook deste fluxo. O fluxo pede ao Claude (API da Anthropic, com busca na web) os anúncios
da cidade e devolve tudo para o site pela API de ingestão.

## Credenciais no n8n (tipo "Header Auth")

| Nome da credencial | Name (cabeçalho) | Value |
|---|---|---|
| IMOBRADAR Webhook | `X-Imobradar-Segredo` | o mesmo valor de `IMOBRADAR_N8N_SEGREDO` no `.env` do site |
| IMOBRADAR API | `Authorization` | `Bearer ` + o valor de `IMOBRADAR_INGEST_TOKEN` do `.env` do site |
| Anthropic API | `x-api-key` | a chave da API da Anthropic |

## No site (.env)

```
IMOBRADAR_N8N_WEBHOOK=https://SEU-N8N/webhook/imobradar-busca
IMOBRADAR_N8N_SEGREDO=um-segredo-longo
```
