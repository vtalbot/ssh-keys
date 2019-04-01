<?php

namespace App\Commands;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function json_encode;
use const JSON_PRETTY_PRINT;
use const PHP_EOL;
use RuntimeException;

trait InteractsWithConfig
{
    protected function readConfig(): array
    {
        if (!$this->configExists()) {
            throw new RuntimeException('SSH-Keys configuration file not found. Please register a token.');
        }

        return json_decode(file_get_contents($this->configPath()), true);
    }

    protected function storeConfig(array $config): void
    {
        file_put_contents($this->configPath(), json_encode($config, JSON_PRETTY_PRINT).PHP_EOL);
    }

    protected function configExists(): bool
    {
        return file_exists($this->configPath());
    }

    protected function configPath(): string
    {
        return $this->homePath() . '/.ssh-keys/config.json';
    }

    protected function homePath(): string
    {
        if (!empty($_SERVER['HOME'])) {
            return $_SERVER['HOME'];
        }

        if (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOME_PATH'])) {
            return $_SERVER['HOMEDRIVE'] . $_SERVER['HOME_PATH'];
        }

        throw new RuntimeException('Cannot determine home directory.');
    }
}
