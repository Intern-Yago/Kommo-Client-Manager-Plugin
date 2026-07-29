# Changelog

Todas as mudanças relevantes deste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

---

## [1.0.0] - 2026-07-29

### 🚀 Adicionado
- **Arquitetura PSR-4 & Namespaces**: Autoloading automático de classes PHP sob a namespace `KCM\`.
- **API Kommo CRM (v4)**: Cliente HTTP para autenticação OAuth2, renovação automática de *Access Token* e consumo dos endpoints da API v4 do Kommo (`/api/v4/contacts`, `/api/v4/account`).
- **Banco de Dados Nativo**: Criação com `dbDelta()` das tabelas customizadas `wp_kcm_clients` e `wp_kcm_logs`.
- **Sincronização de Contatos**: Motor de importação manual e agendamento de sincronização periódica via WP-Cron (`SyncService`).
- **Webhook REST API em Tempo Real**: Endpoint `/wp-json/kcm/v1/webhook` para recepção automática de novos contatos/leads cadastrados ou editados no Kommo CRM.
- **Gerenciamento de Usuários WordPress**: Vinculação automática com usuários WP e opção de criação automática de contas para novos clientes.
- **Painel Administrativo Completo**:
  - **Dashboard**: Métricas em tempo real, status de conexão OAuth e últimas atividades.
  - **Clientes**: Listagem paginada de clientes sincronizados com busca por nome/email/empresa e atalhos para perfis WP.
  - **Sincronização**: Gatilho de sincronização manual e caixa com a URL do Webhook com botão de cópia.
  - **Logs**: Tabela de auditoria do sistema com filtros por nível (`info`, `warning`, `error`) e limpeza de registros.
  - **Configurações**: Gerenciamento de credenciais da API, troca do Código de Autorização OAuth e teste de conexão.
- **Isolamento de Interface**: Ocultação de notificações de terceiros (manageWP, GDPR, Dynamic.ooo, etc.) em todas as telas do plugin para evitar quebras visuais no cabeçalho.

---

## [Planejado]

### 1.1.0
- Barra de progresso para sincronizações extensas.
- Mapeamento avançado de campos personalizados do Kommo para Meta Data do WordPress.

### 2.0.0
- Suporte a múltiplos pipelines e etapas de funil.
- Notificações de eventos via WhatsApp/Webhooks de saída.