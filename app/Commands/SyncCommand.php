<?php

namespace App\Commands;

use App\Services\GitHubProvider;
use App\Services\Provider;
use function array_unique;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use function implode;
use LaravelZero\Framework\Commands\Command;

class SyncCommand extends Command
{
    use InteractsWithConfig;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'sync';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Sync SSH keys for all registered users';

    public function handle(): void
    {
        if (!$this->configExists()) {
            $this->error('Please register at least one user.');
            exit(1);
        }

        $users = $this->readConfig()['users'];

        foreach ($users as $user => $config) {
            $this->performSync($user, $config);
        }
    }

    public function schedule(Schedule $schedule): void
    {
        $schedule->command(static::class)->everyMinute();
    }

    private function performSync(string $user, array $config): void
    {
        $provider = $this->makeProvider($config['provider'], $config['token']);

        $keys = $provider->getSshKeys();

        $authorizedKeys = array_merge($this->getAuthorizedKeys($config['path']), $keys);
        $authorizedKeys = array_unique($authorizedKeys);

        $this->storeAuthorizedKeys($config['path'], $authorizedKeys);
    }

    private function makeProvider(string $name, string $token): Provider
    {
        /** @var Provider $provider */
        if ($name === 'github') {
            $provider = $this->app->make(GitHubProvider::class);
        }

        return $provider->withToken($token);
    }

    private function storeAuthorizedKeys(string $path, array $keys): void
    {
        /** @var Filesystem $filesystem */
        $filesystem = $this->app->make(Filesystem::class);
        if (!$filesystem->exists($path . '/.ssh')) {
            $filesystem->makeDirectory($path . '/.ssh');
        }

        file_put_contents($path . '/.ssh/authorized_keys', implode("\n", $keys));
    }

    private function getAuthorizedKeys(string $path): array
    {
        if (file_exists($path . '/.ssh/authorized_keys')) {
            $authorizedKeys = file_get_contents($path . '/.ssh/authorized_keys');

            return explode("\n", $authorizedKeys);
        }

        return [];
    }
}
