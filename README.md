# Kommo Client Manager for WordPress 🔌

[![Versão](https://img.shields.io/badge/vers%C3%A3o-1.1.0-blue.svg)](https://github.com/intern-yago/Kommo-Client-Manager-Plugin/releases)
[![Licença](https://img.shields.io/badge/licen%C3%A7a-MIT-green.svg)](LICENSE)
[![Documentação](https://img.shields.io/badge/docs-GitHub%20Pages-brightgreen.svg)](https://intern-yago.github.io/Kommo-Client-Manager-Plugin/)

Integração nativa de alta performance entre o **Kommo CRM (amoCRM)** e o **WordPress**.

🌐 **Página de Documentação & OAuth Hub Oficial:**  
[https://intern-yago.github.io/Kommo-Client-Manager-Plugin/](https://intern-yago.github.io/Kommo-Client-Manager-Plugin/)

---

## 🎯 Objetivo

Automatizar o gerenciamento de clientes, criação de contas de usuário no WordPress e sincronização em tempo real quando um lead ou contato for adicionado ou modificado no Kommo CRM.

---

## ✨ Funcionalidades

- ⚡ **Webhook REST API em Tempo Real**: Endpoint `/wp-json/kcm/v1/webhook` para capturar alterações no Kommo.
- 👤 **Criação Automática de Usuários**: Transforma contatos do Kommo em usuários assinantes do WordPress.
- 🔄 **Sincronização em Lote & Agendada**: Motor de sincronização manual ou automática via WP-Cron.
- 🔑 **Autenticação OAuth 2.0 Segura**: Gestão e renovação automática de *Access Token* e *Refresh Token*.
- 📊 **Painel Administrativo Nativo**:
  - **Dashboard**: Status da conexão e métricas do sistema.
  - **Clientes**: Tabela paginada com busca por nome, e-mail e empresa.
  - **Sincronização**: Gatilho manual e copiador rápido da URL do Webhook.
  - **Logs**: Sistema completo de auditoria com filtro de níveis (`info`, `warning`, `error`).
  - **Configurações**: Gerenciamento de credenciais e troca do código de autorização.
- 🛡️ **Segurança Hardened**: Proteção estrita por capacidades de usuário (`manage_options`), sanitização de entradas e validação de payloads.

---

## 📋 Requisitos

- **PHP**: 8.1+
- **WordPress**: 6.0+
- **Kommo CRM**: Conta ativa com acesso a Integrações Privadas

---

## 🚀 Instalação Rápida

1. Acesse as [Releases no GitHub](https://github.com/intern-yago/Kommo-Client-Manager-Plugin/releases) e baixe o arquivo `kommo-client-manager.zip`.
2. No seu painel WordPress, vá em **Plugins &rarr; Adicionar Novo &rarr; Enviar Plugin**.
3. Selecione o arquivo `.zip` e clique em **Instalar Agora**.
4. Clique em **Ativar Plugin**.
5. Acesse o menu **Kommo Manager &rarr; Configurações** no WordPress para inserir suas credenciais da API Kommo.

Para o tutorial detalhado de integração no Kommo, consulte a [Documentação Oficial](https://intern-yago.github.io/Kommo-Client-Manager-Plugin/).

---

## 📄 Licença

Este projeto está licenciado sob a licença [MIT](LICENSE).

**Autor:** Yago