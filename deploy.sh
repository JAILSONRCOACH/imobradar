#!/usr/bin/env bash
# Atualiza o IMOBRADAR no servidor (Hostinger). Rodar dentro da pasta do projeto:
#   bash deploy.sh
set -euo pipefail
cd "$(dirname "$0")"

PHP=${PHP:-/opt/alt/php83/usr/bin/php}
COMPOSER="$PHP $(command -v composer)"

echo "==> Baixando código"
git pull --ff-only

echo "==> Dependências (sem pacotes de desenvolvimento)"
$COMPOSER install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "==> Banco de dados"
$PHP artisan migrate --force

echo "==> Caches"
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan cache:forget busca:resumo >/dev/null 2>&1 || true
$PHP artisan cache:forget busca:cidades >/dev/null 2>&1 || true

echo "==> Pronto"
