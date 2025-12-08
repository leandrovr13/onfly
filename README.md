# 📘 Onfly Travel Orders
Sistema de gerenciamento de pedidos de viagem corporativa  
Projeto desenvolvido para avaliação técnica.

---

## 📦 Stack utilizada

- **Backend:** Laravel 10 + PHP 8 + Sanctum  
- **Frontend:** Vue 3 + Vite + PrimeVue (tema Sakai)  
- **Banco de dados:** MySQL 8  
- **Ambiente:** Docker + Docker Compose  
- **Autenticação:** Token via Sanctum  

---

## 🚀 Como rodar o projeto

### 1. Pré-requisitos

Instale:

- Docker  
- Docker Compose  
- Git  

Verifique:

```
docker --version
docker compose version
git --version
```

---

## 📁 Estrutura do projeto

```
onfly/
  ├── backend/         # API Laravel
  ├── frontend/        # Aplicação Vue 3
  ├── docker-compose.yml
  └── README.md
```

---

## 🐳 2. Subindo os containers

Na raiz do projeto:

```
docker compose up -d --build
```

Serviços iniciados:

| Serviço   | Porta         | Descrição            |
|----------|---------------|----------------------|
| app      | 8080          | API Laravel          |
| frontend | 5173          | SPA Vue 3            |
| mysql    | interno:3306  | Banco MySQL 8        |

---

## 🗄️ 3. Configuração inicial da API (Laravel)

Acesse o container do backend:

```
docker compose exec app bash
```

Dentro do container:

### Instalar dependências

```
composer install
```

### Rodar migrations

```
php artisan migrate
```

### Criar usuário administrador

```
php artisan db:seed --class=AdminUserSeeder
```

Usuário criado:

- **email:** admin@onfly.test  
- **senha:** password  

### (Opcional) Gerar chave da aplicação

```
php artisan key:generate
```

Saia do container:

```
exit
```

---

## 🌐 4. Frontend

O frontend sobe automaticamente no container.  
Acesse:

```
http://localhost:5173
```

---

## 🔑 5. Login / Registro

### Login

Use as credenciais:

- **email:** admin@onfly.test  
- **senha:** password  

### Registro (usuário comum)

A tela `/register` permite criar usuários com:

- Nome  
- Email  
- Telefone  
- Foto (upload)  
- Senha  

Todos usuários criados via registro possuem **role = user**.

---

## 📡 6. Endpoints principais da API

### Autenticação

| Método | Rota               | Descrição                |
|--------|--------------------|--------------------------|
| POST   | /api/auth/login    | Login, retorna token     |
| POST   | /api/auth/register | Cria usuário comum       |
| GET    | /api/user          | Dados do usuário logado  |

---

### Pedidos de Viagem

| Método | Rota                                      | Descrição               |
|--------|-------------------------------------------|--------------------------|
| GET    | /api/travel-orders                        | Lista pedidos           |
| POST   | /api/travel-orders                        | Cria pedido             |
| PATCH  | /api/travel-orders/{id}/status            | Atualiza status         |
| GET    | /api/notifications                        | Lista notificações      |

---

### Filtros disponíveis

```
status=solicitado|aprovado|cancelado
destination=texto
start_date=YYYY-MM-DD
end_date=YYYY-MM-DD
```

A API retorna viagens cujo intervalo de ida/volta **intercepta o range informado**.

---

## 🧪 7. Testando via Postman / Insomnia

### Login

POST `http://localhost:8080/api/auth/login`

Body:

```json
{
  "email": "admin@onfly.test",
  "password": "password"
}
```

Envie o token retornado em:

```
Authorization: Bearer TOKEN
```

---

## 🎨 8. Interface (PrimeVue + Sakai)

O frontend utiliza:

- Toolbar customizada  
- Avatar do usuário com menu dropdown  
- DataTable PrimeVue  
- Dialog de criação de pedido  
- Dropdowns e Calendars  
- Tags de status (success, info, danger)  
- Layout responsivo baseado no tema Sakai  

---

## 📜 9. Scripts úteis

### Reiniciar containers

```
docker compose down && docker compose up -d --build
```

### Acessar container do backend

```
docker compose exec app bash
```

### Logs

Backend:

```
docker compose logs -f app
```

Frontend:

```
docker compose logs -f frontend
```

---

## 🧹 10. Limpeza (reset completo)

```
docker compose down -v
```

---

