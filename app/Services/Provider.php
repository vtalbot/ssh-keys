<?php

namespace App\Services;

interface Provider
{
    public function getSshKeys(): array;

    public function withToken(string $token): self;
}
