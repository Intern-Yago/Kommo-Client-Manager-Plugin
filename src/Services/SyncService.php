<?php

namespace KCM\Services;

use KCM\Api\KommoApi;
use KCM\Models\Client;

if (!defined('ABSPATH')) {
    exit;
}

class SyncService
{
    public static function syncAllContacts(): array
    {
        $api = new KommoApi();
        $page = 1;
        $limit = 50;
        $totalSynced = 0;
        $totalErrors = 0;

        LogService::info('Iniciando sincronização completa de contatos do Kommo...');

        do {
            $response = $api->getContacts($page, $limit);

            if (!$response['success']) {
                $totalErrors++;
                LogService::error('Erro ao buscar contatos na página ' . $page, ['error' => $response['message'] ?? '']);
                break;
            }

            $contacts = $response['data']['_embedded']['contacts'] ?? [];
            if (empty($contacts)) {
                break;
            }

            foreach ($contacts as $contact) {
                try {
                    $parsed = KommoService::parseContactData($contact);
                    if ($parsed['kommo_id'] > 0) {
                        $wpUserId = UserService::createOrMatchUser($parsed);
                        if ($wpUserId) {
                            $parsed['wp_user_id'] = $wpUserId;
                        }

                        Client::save($parsed);
                        $totalSynced++;
                    }
                } catch (\Throwable $e) {
                    $totalErrors++;
                    LogService::error('Erro ao salvar contato durante sincronização', [
                        'exception' => $e->getMessage(),
                        'contact'   => $contact['id'] ?? null,
                    ]);
                }
            }

            $page++;
        } while (count($contacts) === $limit);

        update_option('kcm_last_sync_time', current_time('mysql'));
        update_option('kcm_last_sync_count', $totalSynced);

        LogService::info("Sincronização finalizada. Sucesso: {$totalSynced}, Erros: {$totalErrors}");

        return [
            'success'      => true,
            'total_synced' => $totalSynced,
            'total_errors' => $totalErrors,
        ];
    }

    public static function runScheduledSync(): void
    {
        self::syncAllContacts();
    }
}
