<?php

namespace App\Services\GitHub;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Talks to GitHub as our App: mints the short-lived JWT used to authenticate
 * App-level requests, and exchanges it for per-installation access tokens
 * used to actually clone repositories and query the API on a team's behalf.
 */
class GitHubAppClient
{
    private const API_BASE = 'https://api.github.com';

    /**
     * Get a cached installation access token, minting a new one if needed.
     * Tokens are valid for an hour; cached for 50 minutes to be safe.
     */
    public function installationToken(int $installationId): string
    {
        return Cache::remember("github.installation_token.{$installationId}", now()->addMinutes(50), function () use ($installationId) {
            $response = Http::withToken($this->appJwt())
                ->withHeaders(['Accept' => 'application/vnd.github+json'])
                ->post(self::API_BASE."/app/installations/{$installationId}/access_tokens")
                ->throw();

            return $response->json('token');
        });
    }

    /**
     * Fetch the account this installation belongs to (a user or organization).
     *
     * @return array{login: string, type: string}
     */
    public function installationAccount(int $installationId): array
    {
        $response = Http::withToken($this->appJwt())
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->get(self::API_BASE."/app/installations/{$installationId}")
            ->throw();

        return [
            'login' => $response->json('account.login'),
            'type' => $response->json('account.type'),
        ];
    }

    /**
     * List the repositories this installation has access to.
     *
     * @return array<int, array{full_name: string, default_branch: string}>
     */
    public function installationRepositories(int $installationId): array
    {
        return Cache::remember("github.installation_repositories.{$installationId}", now()->addMinutes(2), function () use ($installationId) {
            $response = Http::withToken($this->installationToken($installationId))
                ->withHeaders(['Accept' => 'application/vnd.github+json'])
                ->get(self::API_BASE.'/installation/repositories')
                ->throw();

            return array_map(
                fn (array $repo) => ['full_name' => $repo['full_name'], 'default_branch' => $repo['default_branch']],
                $response->json('repositories', []),
            );
        });
    }

    /**
     * List a repository's branches.
     *
     * @return array<int, string>
     */
    public function repositoryBranches(int $installationId, string $repository): array
    {
        $response = Http::withToken($this->installationToken($installationId))
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->get(self::API_BASE."/repos/{$repository}/branches", ['per_page' => 100])
            ->throw();

        return array_map(fn (array $branch) => $branch['name'], $response->json());
    }

    /**
     * Sign a short-lived JWT with the App's private key, per GitHub's App
     * authentication scheme (RS256, iss = App ID).
     */
    private function appJwt(): string
    {
        $privateKey = config('services.github.private_key');
        $appId = config('services.github.app_id');

        if (! $privateKey || ! $appId) {
            throw new RuntimeException('The GitHub App is not configured.');
        }

        $header = $this->base64UrlEncode($this->jsonEncode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode($this->jsonEncode([
            'iat' => now()->subSeconds(60)->timestamp,
            'exp' => now()->addMinutes(9)->timestamp,
            'iss' => $appId,
        ]));

        $signingInput = "{$header}.{$payload}";

        if (! openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Failed to sign the GitHub App JWT.');
        }

        return "{$signingInput}.".$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function jsonEncode(array $data): string
    {
        $encoded = json_encode($data);

        if ($encoded === false) {
            throw new RuntimeException('Failed to JSON-encode a JWT segment.');
        }

        return $encoded;
    }
}
