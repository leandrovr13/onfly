# Onfly Travel Orders

Sistema de gerenciamento de pedidos de viagem corporativa.  
Projeto desenvolvido para avaliação técnica.

---

## 📦 Stack utilizada

**Backend**

- Laravel 10  
- PHP 8  
- Laravel Sanctum  
- Notificações via Database  

**Frontend**

- Vue 3 + Vite  
- PrimeVue (tema Aura)  
- PrimeFlex + PrimeIcons  

**Infraestrutura**

- Docker + Docker Compose  
- MySQL 8  

---

## 🚀 Como rodar o projeto

### 1. Pré-requisitos

Instale:

- Docker  
- Docker Compose  
- Git  

Verifique:

```bash
docker --version
docker compose version
git --version
```

---

## 📁 Estrutura do projeto

```
onfly/
  ├── backend/        # API Laravel
  ├── frontend/       # SPA Vue 3
  ├── docker-compose.yml
  └── README.md
```

---

## 🐳 2. Subindo os containers

Na raiz:

```bash
docker compose up -d --build
```

Serviços:

| Serviço   | Porta         | Descrição |
|----------|--------------|-----------|
| app      | 8080         | API Laravel |
| frontend | 5173         | Aplicação Vue 3 |
| mysql    | interno 3306 | Banco de dados |

---

## 🗄️ 3. Configuração da API

Acesse o container:

```bash
docker compose exec app bash
```

Instale dependências:

```bash
composer install
```

Rode migrations:

```bash
php artisan migrate
```

Crie usuário admin:

```bash
php artisan db:seed --class=AdminUserSeeder
```

Admin padrão:

- email: `admin@onfly.test`  
- senha: `password`

---

## 🌐 4. Frontend

A SPA estará disponível em:

```
http://localhost:5173
```

---

## 🔑 5. Autenticação

### Login

Use o admin criado:

- Email: `admin@onfly.test`
- Senha: `password`

### Registro

A tela `/register` permite criar usuários:

- Nome  
- Email  
- Telefone  
- Avatar  
- Senha  

Usuários cadastrados têm `role = user`.

---

## 📡 6. Endpoints principais

### Auth

| Método | Rota | Descrição |
|--------|-------|-----------|
| POST | `/api/auth/login` | Login com token |
| POST | `/api/auth/register` | Registra usuário |
| POST | `/api/auth/profile` | Atualiza perfil |
| GET  | `/api/user` | Retorna usuário autenticado |

### Travel Orders

| Método | Rota | Descrição |
|--------|-------|----------|
| GET | `/api/travel-orders` | Lista pedidos (com filtros) |
| POST | `/api/travel-orders` | Cria pedido |
| PATCH | `/api/travel-orders/{id}/status` | Atualiza status (admin) |

Regras:

- Pedido sempre é criado como `solicitado`.
- Status só pode mudar para `aprovado` ou `cancelado`.
- Pedido aprovado **não pode** ser cancelado.

### Notificações

| Método | Rota | Descrição |
|--------|-------|----------|
| GET | `/api/notifications` | Lista notificações |
| POST | `/api/notifications/read` | Marca como lidas |

As notificações são armazenadas no banco via `database notifications`.

---

## 🔎 7. Filtros suportados

Na rota `/api/travel-orders`:

- `status=solicitado|aprovado|cancelado`
- `destination=texto`
- `id=ID`
- `start_date=YYYY-MM-DD`
- `end_date=YYYY-MM-DD`

A API retorna viagens **que intersectam** o intervalo solicitado.

---

## 🧪 8. Testes automatizados

### Como executar

Dentro do container backend:

```bash
docker compose exec app bash
php artisan test
```

### Importante

Os testes **não usam o MySQL real**.  
A suíte utiliza **SQLite em memória**, configurado no bootstrap de testes:

- Banco da aplicação permanece intacto.
- Rodar testes é seguro e repetível.

### O que é testado

**Auth**
- Registro com token e payload
- Login e erro de login
- Atualização de perfil (incluindo senha e avatar)

**Travel Orders**
- Usuário comum vê apenas seus pedidos
- Admin vê todos
- Filtro por data (interseção)
- Criação com status padrão
- Datas inválidas não são aceitas
- Admin não pode cancelar pedido já aprovado
- Usuário comum não pode alterar status

**Notificações**
- Listagem
- Marcação como lidas
- Envio automático quando pedido é aprovado/cancelado

**Models**
- Relacionamentos
- Cast de datas
- `User::isAdmin()`

---

## 🏛 9. Arquitetura da aplicação

### Backend (Laravel)

- Controllers enxutos e claros
- Regras de validação no próprio controller (simples e direto)
- Autenticação via Sanctum
- Notificações via Laravel Notifications (`database`)
- Regra de interseção de datas implementada diretamente no query builder
- Escolha proposital: **evitar over-engineering**  
  (services/repositories seriam desnecessários num teste técnico)

Motivação:  
> Facilitar leitura do avaliador e seguir práticas idiomáticas do Laravel.

---

### Frontend (Vue 3 + PrimeVue)

- SPA com Vue Router (login, registro, dashboard, perfil)
- Estado simples baseado em `localStorage`
- API centralizada em `services/api.js`
- Componentes PrimeVue (Datatable, Dialog, Toast, Password etc.)
- Tema Aura com suporte a dark mode
- Formulário de perfil com máscara de telefone e validações de senha

Implementação da notificação:

- Ícone “sininho” no header
- Badge com contador de notificações não lidas
- Dropdown estilo Facebook
- Marcação como lida ao abrir o dropdown

---

## 📜 10. Scripts úteis

Reiniciar containers:

```bash
docker compose down && docker compose up -d --build
```

Acessar backend:

```bash
docker compose exec app bash
```

Logs:

```bash
docker compose logs -f app
docker compose logs -f frontend
```

Resetar tudo:

```bash
docker compose down -v
```

---
