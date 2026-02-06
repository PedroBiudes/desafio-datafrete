# Desafio Datafrete

Sistema para cálculo e cadastro de distâncias entre CEPs, com validação via API externa, processamento assíncrono e interface web.

---

## 📌 Visão geral

Este projeto permite:

- Calcular a distância entre dois CEPs
- Calcular a distância via estrada entre dois CEPs
- Validar CEPs utilizando a BrasilAPI
- Persistir os cálculos em banco de dados
- Importar múltiplos cálculos via arquivo CSV
- Processar importações de forma assíncrona utilizando filas
- Visualizar dados através de uma interface web

---

## 🧱 Arquitetura

- **Backend:** Laravel 8 (PHP 7.4)
- **Frontend:** Vue.js 2 + Bootstrap 4
- **Banco de dados:** MySQL
- **Fila:** RabbitMQ
- **Cache:** Redis
- **Infra:** Docker + Docker Compose

## 📂 Estrutura do projeto

desafio-datafrete/
├── backend/ # Laravel (API)
├── frontend/ # Vue 2 (SPA)
├── docker/ # Dockerfiles e configs
├── docker-compose.yml
└── README.md


---

## 🚀 Subindo o projeto

### Pré-requisitos
- Docker
- Docker Compose

### Subir containers
```bash
docker compose up -d --build
Serviços disponíveis:

Backend (API): http://localhost:8080

Frontend (Web): http://localhost:8081

RabbitMQ UI: http://localhost:15672

usuário: guest
senha: guest

🗄️ Banco de dados
Rodar migrations
docker compose exec backend php artisan migrate

📥 Importação CSV

Formato do arquivo
cep_origem,cep_destino
01001000,01311000
01001000,20040002

🧠 Processamento assíncrono

Cada importação CSV gera um job na fila

As linhas são processadas uma a uma

Erros por linha são registrados sem interromper o processo

Status da importação:

queued

processing

done

failed

⚡ Cache de CEPs

As consultas à API externa são cacheadas em Redis

TTL padrão: 7 dias

Evita chamadas repetidas e bloqueio por rate-limit

🖥️ Frontend

Funcionalidades disponíveis:

Cadastro manual de distância

Upload de CSV

Listagem de distâncias

Listagem de importações e status

Frontend acessível em:

http://localhost:8081

🧾 Logs

Logs estruturados gerados via Monolog

Localizados em:

backend/storage/logs/laravel.log
---