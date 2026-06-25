# ERP de Estoque — API (Laravel 12 + MySQL)

API REST desacoplada para um ERP de estoque: cadastro de **produtos**, registro de **compras** (entrada de estoque + custo médio ponderado) e **vendas** (saída de estoque + cálculo de lucro), com cancelamento de venda. Ambiente totalmente dockerizado (backend + MySQL).

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 12 / PHP 8.3 |
| Banco | MySQL 8.0 (provisionado via Docker) |
| Arquitetura | Form Request → Service → API Resource |
| Consistência | `DB::transaction` + `lockForUpdate` |

## Estrutura (desacoplada)

```
cadastro/
├── docker-compose.yml      # backend + frontend + mysql
├── backend/                # API Laravel
│   ├── Dockerfile
│   └── entrypoint.sh
└── frontend/               # SPA Vue 3 + Vite + Tailwind
    └── Dockerfile
```

## Como rodar

Pré-requisito: Docker + Docker Compose.

```bash
docker compose up --build
```

Isso sobe **MySQL** (cria o schema `erp_estoque`), o **backend** (aguarda o banco, aplica as migrations e sobe a API) e o **frontend** (Vite dev server).

- Frontend: **http://localhost:5173**
- API: **http://localhost:8000/api**
- Healthcheck: http://localhost:8000/up
- MySQL exposto no host em **localhost:3307** (interno: `mysql:3306`) — porta 3307 para não colidir com um MySQL local na 3306.

Resetar o banco (apaga o volume): `docker compose down -v`.

### Dados de demonstração (seed)

Para popular o banco com um catálogo, compras e vendas de exemplo (inclui uma venda **cancelada** e custo médio já deslocado por uma segunda compra):

```bash
# popula sem apagar o que já existe
docker compose exec backend php artisan db:seed

# ou: zera tudo e popula do zero
docker compose exec backend php artisan migrate:fresh --seed
```

O seeder ([DatabaseSeeder](backend/database/seeders/DatabaseSeeder.php)) usa os próprios Services, então estoque, custo médio e lucro saem calculados como em produção. Gera 5 produtos, 2 compras e 4 vendas (3 concluídas + 1 cancelada) — bom para as telas de listagem do front.

## Endpoints

| Método | Rota | Descrição |
|---|---|---|
| GET | `/api/dashboard` | Visão geral: KPIs, alertas de estoque e atividade recente |
| POST | `/api/produtos` | Cadastra produto (estoque inicia em 0) |
| GET | `/api/produtos` | Lista: id, nome, custo_medio, preco_venda, estoque |
| POST | `/api/compras` | Registra compra (entrada + atualiza custo médio) |
| GET | `/api/compras` | Lista compras com itens |
| POST | `/api/vendas` | Registra venda (saída + calcula lucro) |
| GET | `/api/vendas` | Lista vendas com itens |
| POST | `/api/vendas/{venda}/cancelar` | Cancela a venda (reverte estoque) |

### Exemplos

```bash
# Cadastrar produto
curl -X POST localhost:8000/api/produtos -H "Accept: application/json" \
  -H "Content-Type: application/json" -d '{"nome":"Caneta Azul","preco_venda":50}'

# Compra (entrada + custo médio)
curl -X POST localhost:8000/api/compras -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"fornecedor":"Fornecedor X","produtos":[{"id":1,"quantidade":50,"preco_unitario":20}]}'

# Venda (retorna total e lucro)
curl -X POST localhost:8000/api/vendas -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"cliente":"Fulano","produtos":[{"id":1,"quantidade":2,"preco_unitario":50}]}'
```

Resposta da venda (`201`):
```json
{ "data": { "id": 1, "cliente": "Fulano", "status": "concluida",
  "total": 100, "lucro": 56.25,
  "itens": [{ "produto_id": 1, "nome": "Caneta Azul", "quantidade": 2,
              "preco_unitario": 50, "custo_unitario": 21.875 }] } }
```

Estoque insuficiente (`422`):
```json
{ "message": "Estoque insuficiente para o produto \"Caneta Azul\". Disponível: 1, solicitado: 2." }
```

Dashboard (`GET /api/dashboard?estoque_minimo=10`):
```json
{ "data": {
  "valor_em_estoque": 72688, "unidades_em_estoque": 248,
  "faturamento": 14310, "lucro": 4778, "margem_media": 33.4,
  "vendas_concluidas": 4, "alertas_estoque": 2,
  "estoque_baixo": [
    { "id": 6, "nome": "Marca-Texto Amarelo", "estoque": 0, "status": "sem_estoque" },
    { "id": 7, "nome": "Grampeador 26/6", "estoque": 4, "status": "baixo" }
  ],
  "atividade_recente": [
    { "tipo": "venda", "descricao": "Livraria Saber", "valor": 510, "status": "concluida", "data": "..." },
    { "tipo": "compra", "descricao": "Info Atacado", "valor": -24400, "status": null, "data": "..." }
  ]
} }
```
> `margem_media` em % (lucro/faturamento). Em `atividade_recente`, venda tem `valor` positivo e compra negativo. `estoque_minimo` é opcional (padrão 10).

## Regras de negócio

- **Custo médio ponderado móvel** (na compra): `novo_custo = (estoque·custo_medio + qtd·preço) / (estoque + qtd)`. Quando o estoque é 0, reseta para o preço da compra. Guardado com 4 casas.
- **Lucro** (na venda): grava-se um *snapshot* do custo médio em cada item (`custo_unitario`); `lucro = Σ (preço_venda − custo_unitario) · qtd`. Assim o lucro histórico não muda quando compras futuras alteram o custo médio.
- **Cancelamento**: só vendas `concluida`; devolve o estoque e marca `cancelada`. Não reconstitui o custo médio (a venda nunca o alterou) — simplificação consciente para o escopo.
- **Atomicidade**: compra e venda rodam em transação; a venda valida o estoque de **todos** os itens antes de baixar qualquer um (rollback total se faltar).

## Testes

```bash
docker compose exec backend php artisan test
```

Cobrem os caminhos críticos: custo médio ponderado, lucro, estoque insuficiente (422 + rollback), cancelamento, arredondamento com dízima e produto repetido no payload.

## Frontend (Vue 3)

SPA desacoplada em [`frontend/`](frontend) — Vue 3 (`<script setup>` + TypeScript), Vite, Pinia, Vue Router, Tailwind CSS e axios. Telas: **Visão geral** (dashboard), **Produtos**, **Compras** e **Vendas**, com tema claro/escuro. Consome a API em `VITE_API_URL` (padrão `http://localhost:8000/api`).

Via Docker já sobe junto (`docker compose up --build` → http://localhost:5173). Para rodar localmente sem Docker:

```bash
cd frontend
npm install
npm run dev            # http://localhost:5173
npm run build          # type-check (vue-tsc) + build de produção
```

> **Produção:** o `Dockerfile` atual roda o Vite dev server (combina com o `docker-compose`). Para deploy, trocar por um build multi-stage servido por nginx, com `VITE_API_URL` como build-arg (o Vite injeta a variável em tempo de build).

## Notas

- **Auth**: o desafio não pede autenticação; o Sanctum vem do scaffold do Laravel e pode ser ignorado.
- **CORS**: liberado por padrão para `api/*` (middleware `HandleCors` do Laravel 12) — front desacoplado funciona sem configuração extra em dev.
