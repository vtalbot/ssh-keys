<?php

namespace App\Services;

use Github\Client;

class GitHubProvider implements Provider
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }
    
    public function getSshKeys(): array
    {
        return collect($this->client->currentUser()->keys()->all())
            ->pluck('key')
            ->all();
    }

    public function withToken(string $token): Provider
    {
        $this->client->authenticate($token, Client::AUTH_URL_TOKEN);

        return $this;
    }
}
