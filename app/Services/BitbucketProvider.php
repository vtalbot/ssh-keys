<?php

namespace App\Services;

use Bitbucket\API\Authentication\Basic;
use Bitbucket\API\Http\ClientInterface;
use Bitbucket\API\Http\Listener\BasicAuthListener;
use Bitbucket\API\Users;

class BitbucketProvider implements Provider
{
    /** @var Users */
    private $client;

    /** @var string */
    private $user;

    /** @var string */
    private $token;

    public function __construct()
    {
        $this->client = new Users;
    }
    
    public function getSshKeys(): array
    {
        $this->client->setCredentials(new Basic($this->user, $this->token));

        return collect(json_decode($this->client->sshKeys()->all($this->user)->getContent(), true))
            ->pluck('key')
            ->all();
    }

    public function withToken(string $token): Provider
    {
        $this->token = $token;

        return $this;
    }

    public function withUrl(string $url): Provider
    {
        return $this;
    }

    public function withUser(string $user): Provider
    {
        $this->user = $user;

        return $this;
    }
}
