=== Kommo Client Manager ===
Contributors: intern-yago
Tags: kommo, crm, amocrm, integration, sync, users
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.1
Stable tag: 1.3.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integração nativa entre Kommo CRM (amoCRM) e WordPress para sincronização de clientes e criação automática de usuários.

== Description ==

O **Kommo Client Manager** permite conectar sua conta do Kommo CRM ao seu site WordPress com suporte a autenticação OAuth 2.0, renovação de tokens, criação automática de contas de usuário e sincronização via Webhooks.

== Installation ==

1. Baixe o pacote ZIP do plugin.
2. No WordPress, acesse Plugins > Adicionar Novo > Enviar Plugin.
3. Envie o arquivo ZIP e ative o plugin.
4. Acesse o menu Kommo Manager > Configurações para realizar a conexão inicial.

== Changelog ==

= 1.3.2 =
* Botão rápido "Cadastrar E-mail" para clientes que foram sincronizados sem e-mail, liberando a geração do Link VIP.

= 1.3.1 =
* Formulário de cadastro manual de cliente no painel de administração.

= 1.3.0 =
* Autenticação de 6 dígitos via e-mail para ativação da Área VIP.
* Link seguro de primeiro acesso e definição de senha.

= 1.1.1 =
* Correção do fluxo de envio das credenciais OAuth (auto-salvar credenciais ao conectar).
* Suporte a fallback de requisição OAuth (JSON e x-www-form-urlencoded).
* Forçar esquema HTTPS na URL de redirecionamento.
* Logs avançados de diagnóstico em caso de falha de conexão.

= 1.1.0 =
* Adicionada nova página de documentação no GitHub Pages.
* Adicionadas verificações estritas de permissões nas páginas administrativas.
* Melhorias na sanitização e validação de dados de Webhook.
* Adicionados workflows de CI/CD para release automatizada.

= 1.0.0 =
* Lançamento inicial do plugin.
