<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecuritySummaryController extends Controller
{
    /**
     * Get a summary of the user's authentication methods and security status.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        // Load connected accounts
        $user->load('connectedAccounts');

        // Determine authentication methods
        $hasPassword = ! is_null($user->password) && $user->password !== '';
        $hasTwoFactor = ! is_null($user->two_factor_confirmed_at);

        // Get connected social accounts
        $socialAccounts = $user->connectedAccounts->map(fn ($account) => [
            'id' => $account->id,
            'provider' => $account->provider,
            'provider_name' => $this->getProviderDisplayName($account->provider),
            'connected_at' => $account->created_at->toIso8601String(),
        ])->values();

        // Calculate security score (0-100)
        $score = 0;
        if ($hasPassword) {
            $score += 40;
        }
        if ($hasTwoFactor) {
            $score += 40;
        }
        if ($socialAccounts->isNotEmpty()) {
            $score += 20;
        }

        return response()->json([
            'authentication' => [
                'password' => [
                    'enabled' => $hasPassword,
                    'last_changed_at' => $user->password_updated_at?->toIso8601String(),
                ],
                'two_factor' => [
                    'enabled' => $hasTwoFactor,
                    'confirmed_at' => $user->two_factor_confirmed_at?->toIso8601String(),
                ],
                'social_accounts' => $socialAccounts,
            ],
            'security_score' => $score,
            'recommendations' => $this->getRecommendations($hasPassword, $hasTwoFactor, $socialAccounts->isNotEmpty()),
        ]);
    }

    /**
     * Get the display name for a social provider.
     */
    private function getProviderDisplayName(string $provider): string
    {
        return match ($provider) {
            'github' => 'GitHub',
            'google' => 'Google',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            default => ucfirst($provider),
        };
    }

    /**
     * Get security recommendations based on current settings.
     *
     * @return list<array{text: string, priority: string, action?: string}>
     */
    private function getRecommendations(bool $hasPassword, bool $hasTwoFactor, bool $hasSocial): array
    {
        $recommendations = [];

        if (! $hasTwoFactor) {
            $recommendations[] = [
                'text' => 'Enable two-factor authentication for enhanced security',
                'priority' => 'high',
                'action' => '/settings/two-factor',
            ];
        }

        if (! $hasPassword && ! $hasSocial) {
            $recommendations[] = [
                'text' => 'Set a password or connect a social account to secure your account',
                'priority' => 'high',
                'action' => '/settings/password',
            ];
        }

        if (! $hasSocial && $hasPassword) {
            $recommendations[] = [
                'text' => 'Connect a social account for easier sign-in',
                'priority' => 'low',
                'action' => '/settings/connected-accounts',
            ];
        }

        return $recommendations;
    }
}
