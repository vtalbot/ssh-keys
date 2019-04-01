<?php

namespace App\Services;

use Gitlab\Client;

class GitLabProvider implements Provider
{
    /** @var string */
    private $url = 'https://gitlab.com';

    /** @var string */
    private $token;

    /** @var Client */
    private $client;

    public function getSshKeys(): array
    {
        $client = $this->getClient();

        return collect($client->users()->keys())
            ->pluck('key')
            ->all();
    }

    private function getClient(): Client
    {
        if ($this->client) {
            return $this->client;
        }

        $this->client = Client::create($this->url)->authenticate($this->token, Client::AUTH_URL_TOKEN);

        return $this->client;
    }

    public function withToken(string $token): Provider
    {
        $this->token = $token;

        return $this;
    }

    public function withUrl(string $url): Provider
    {
        $this->url = $url;

        return $this;
    }
}
