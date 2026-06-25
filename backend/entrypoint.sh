#!/bin/sh
set -e

cd /var/www/html

# 1) Garante o arquivo .env (em um clone limpo o .env não existe — é gitignored).
if [ ! -f .env ]; then
    echo "==> Criando .env a partir do .env.example"
    cp .env.example .env
fi

# 2) Garante as dependências (caso ainda não estejam instaladas).
if [ ! -f vendor/autoload.php ]; then
    echo "==> Instalando dependências do Composer"
    composer install --no-interaction --prefer-dist
fi

# 2.1) Sincroniza as credenciais de banco do ambiente (docker-compose) para o .env.
#      Necessário porque `php artisan serve` propaga o .env aos workers HTTP, e o
#      valor do .env vence as variáveis de ambiente herdadas do container.
for var in DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD; do
    val=$(printenv "$var" || true)
    [ -z "$val" ] && continue
    if grep -q "^${var}=" .env; then
        sed -i "s|^${var}=.*|${var}=${val}|" .env
    else
        echo "${var}=${val}" >> .env
    fi
done

# 3) Garante a APP_KEY.
if ! grep -q '^APP_KEY=base64:' .env; then
    echo "==> Gerando APP_KEY"
    php artisan key:generate --force
fi

# 4) Espera o MySQL e roda as migrations (migrate é idempotente).
echo "==> Aguardando o banco e aplicando migrations"
until php artisan migrate --force 2>/dev/null; do
    echo "    banco indisponível, nova tentativa em 3s..."
    sleep 3
done

# 5) Sobe o servidor de desenvolvimento.
echo "==> Backend pronto em http://0.0.0.0:8000/api"
exec php artisan serve --host=0.0.0.0 --port=8000
