<?php
namespace App\Services;

use App\Services\AI\OmniRouterService;
use App\Services\AI\ProviderAdapter;

class AIService
{
    public static function reply(string $provider, array $config, string $userMessage, $teamId): string
    {
        return ProviderAdapter::reply($provider, $config, $userMessage, $teamId);
    }

    public static function getSettings($teamId): array
    {
        return ProviderAdapter::getSettings($teamId);
    }

    public static function testConnection(string $provider, string $apiKey): array
    {
        return ProviderAdapter::testConnection($provider, $apiKey);
    }

    public static function listModels($teamId): array
    {
        return ProviderAdapter::listModels($teamId);
    }

    public static function ensureTables(): void
    {
        ProviderAdapter::ensureTables();
    }

    public static function listAvailableProviders($teamId): array
    {
        return OmniRouterService::listAvailableProviders($teamId);
    }

    public static function routeAdvanced(string $provider, array $config, string $userMessage, $teamId): array
    {
        return OmniRouterService::route($provider, $config, $userMessage, $teamId);
    }
}
