# Frontend Vue 3 (Vite) — modo dev, combina com o serviço do docker-compose (porta 5173).
# Em produção, trocar por um build multi-stage servido por nginx (ver README).
FROM node:20-alpine

WORKDIR /app

# Camada cacheável de dependências.
COPY package*.json ./
RUN npm ci

# Restante do código.
COPY . .

EXPOSE 5173

# --host 0.0.0.0 para o servidor ser acessível fora do container.
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
