<?php

namespace KCM\Services;

use KCM\Api\KommoApi;
use KCM\Models\Client;

if (!defined('ABSPATH')) {
    exit;
}

class KommoService
{
    public static function testConnection(): array
    {
        $api = new KommoApi();
        return $api->testConnection();
    }

    public static function parseContactData(array $contact): array
    {
        $kommo_id = (int) ($contact['id'] ?? 0);
        $name     = sanitize_text_field(trim($contact['name'] ?? ''));
        $email    = '';
        $phone    = '';
        $company  = sanitize_text_field(trim($contact['company']['name'] ?? ''));

        if (!empty($contact['custom_fields_values']) && is_array($contact['custom_fields_values'])) {
            foreach ($contact['custom_fields_values'] as $field) {
                $code = strtoupper($field['field_code'] ?? '');
                $values = $field['values'] ?? [];

                if ($code === 'EMAIL' && !empty($values[0]['value'])) {
                    $email = sanitize_email(trim($values[0]['value']));
                } elseif ($code === 'PHONE' && !empty($values[0]['value'])) {
                    $phone = sanitize_text_field(trim($values[0]['value']));
                }
            }
        }

        return [
            'kommo_id' => $kommo_id,
            'name'     => $name,
            'email'    => $email,
            'phone'    => $phone,
            'company'  => $company,
            'status'   => 'active',
        ];
    }

    public static function handleWebhook(\WP_REST_Request $request): \WP_REST_Response
    {
        $params = $request->get_params();

        if (empty($params) || !is_array($params)) {
            return new \WP_REST_Response(['status' => 'error', 'message' => 'Payload inválido'], 400);
        }

        LogService::info('Webhook recebido do Kommo', ['params' => $params]);

        // Process contact additions/updates
        if (!empty($params['contacts']['add'])) {
            foreach ($params['contacts']['add'] as $contact) {
                self::processWebhookContact($contact);
            }
        }

        if (!empty($params['contacts']['update'])) {
            foreach ($params['contacts']['update'] as $contact) {
                self::processWebhookContact($contact);
            }
        }

        return new \WP_REST_Response(['status' => 'success'], 200);
    }

    private static function processWebhookContact(array $contact): void
    {
        $parsed = self::parseContactData($contact);
        if ($parsed['kommo_id'] > 0) {
            $wpUserId = UserService::createOrMatchUser($parsed);
            if ($wpUserId) {
                $parsed['wp_user_id'] = $wpUserId;
            }
            Client::save($parsed);
            LogService::info('Cliente atualizado via webhook', ['kommo_id' => $parsed['kommo_id']]);
        }
    }
}
