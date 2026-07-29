<?php

namespace KCM\Api;

use KCM\Services\SettingsService;
use KCM\Services\LogService;

if (!defined('ABSPATH')) {
    exit;
}

class KommoApi
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = SettingsService::getBaseUrl();
    }

    public function exchangeAuthCode(string $code): array
    {
        if (empty($this->baseUrl)) {
            return ['success' => false, 'message' => 'Subdomínio do Kommo não configurado.'];
        }

        $clientId     = SettingsService::get('client_id');
        $clientSecret = SettingsService::get('client_secret');
        $redirectUri  = SettingsService::get('redirect_uri');

        $url = $this->baseUrl . '/oauth2/access_token';

        $body = [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
        ];

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($body),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            $msg = $response->get_error_message();
            LogService::error('Erro ao trocar Auth Code no Kommo', ['error' => $msg]);
            return ['success' => false, 'message' => $msg];
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($statusCode !== 200 || empty($data['access_token'])) {
            $errorMsg = $data['detail'] ?? $data['title'] ?? 'Falha na autenticação com Kommo.';
            LogService::error('Falha na resposta do token Kommo', ['status' => $statusCode, 'data' => $data]);
            return ['success' => false, 'message' => $errorMsg];
        }

        SettingsService::saveTokens(
            $data['access_token'],
            $data['refresh_token'],
            (int) ($data['expires_in'] ?? 86400)
        );

        LogService::info('Token de acesso do Kommo obtido com sucesso!');

        return ['success' => true, 'message' => 'Conexão realizada com sucesso!'];
    }

    public function refreshToken(): bool
    {
        $refreshToken = SettingsService::get('refresh_token');
        if (empty($refreshToken) || empty($this->baseUrl)) {
            return false;
        }

        $clientId     = SettingsService::get('client_id');
        $clientSecret = SettingsService::get('client_secret');
        $redirectUri  = SettingsService::get('redirect_uri');

        $url = $this->baseUrl . '/oauth2/access_token';

        $body = [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
            'redirect_uri'  => $redirectUri,
        ];

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($body),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            LogService::error('Erro ao atualizar token no Kommo', ['error' => $response->get_error_message()]);
            return false;
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($statusCode !== 200 || empty($data['access_token'])) {
            LogService::error('Falha ao renovar token Kommo', ['status' => $statusCode, 'response' => $data]);
            return false;
        }

        SettingsService::saveTokens(
            $data['access_token'],
            $data['refresh_token'],
            (int) ($data['expires_in'] ?? 86400)
        );

        LogService::info('Token do Kommo renovado com sucesso.');

        return true;
    }

    private function getValidAccessToken(): ?string
    {
        if (SettingsService::hasValidToken()) {
            return SettingsService::get('access_token');
        }

        if ($this->refreshToken()) {
            return SettingsService::get('access_token');
        }

        return null;
    }

    public function request(string $endpoint, string $method = 'GET', array $body = []): array
    {
        $token = $this->getValidAccessToken();
        if (!$token) {
            return ['success' => false, 'message' => 'Token de acesso não disponível ou expirado.'];
        }

        $url = $this->baseUrl . $endpoint;

        $args = [
            'method'  => strtoupper($method),
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 30,
        ];

        if (!empty($body) && in_array(strtoupper($method), ['POST', 'PATCH', 'PUT'], true)) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $msg = $response->get_error_message();
            LogService::error('Erro em requisição API Kommo', ['endpoint' => $endpoint, 'error' => $msg]);
            return ['success' => false, 'message' => $msg];
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $rawBody = wp_remote_retrieve_body($response);
        $data = json_decode($rawBody, true) ?: [];

        if ($statusCode >= 400) {
            LogService::error('Erro na API Kommo', ['endpoint' => $endpoint, 'status' => $statusCode, 'response' => $data]);
            return ['success' => false, 'status' => $statusCode, 'data' => $data, 'message' => "Erro HTTP $statusCode"];
        }

        return ['success' => true, 'status' => $statusCode, 'data' => $data];
    }

    public function testConnection(): array
    {
        $res = $this->request('/api/v4/account');
        if ($res['success']) {
            return [
                'success' => true,
                'account_name' => $res['data']['name'] ?? 'Minha Conta Kommo',
                'subdomain'    => $res['data']['subdomain'] ?? '',
            ];
        }

        return ['success' => false, 'message' => $res['message'] ?? 'Não foi possível conectar ao Kommo.'];
    }

    public function getContacts(int $page = 1, int $limit = 50): array
    {
        $endpoint = "/api/v4/contacts?limit={$limit}&page={$page}";
        return $this->request($endpoint);
    }

    public function getLeads(int $page = 1, int $limit = 50): array
    {
        $endpoint = "/api/v4/leads?limit={$limit}&page={$page}&with=contacts";
        return $this->request($endpoint);
    }
}
