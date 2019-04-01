<?php

namespace App\Commands;

use function dirname;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use function in_array;
use LaravelZero\Framework\Commands\Command;
use function preg_match;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class RegisterCommand extends Command
{
    use InteractsWithConfig;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'register {provider : Service that will provide the keys [Github, GitLab]}
                                     {token : Personal access token with rights to read the SSH keys}
                                     {user : Local user that will be given the keys}
                                     {--url= : Custom url for the provider}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Register a provider with a given token to fetch public SSH keys for a given user';

    public function handle(Filesystem $filesystem): void
    {
        $provider = $this->argument('provider');

        if (!in_array($provider, ['github', 'gitlab'])) {
            $this->error('The given provider is not supported. Please use "github" or "gitlab".');
            exit(1);
        }

        $token = $this->argument('token');
        $user = $this->argument('user');

        $process = new Process(['getent', 'passwd', $user]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $userDetails = $process->getOutput();
        preg_match('/^([^:]+:){5}(?P<path>[^:]+)/', $userDetails, $matches);

        $homeDirectory = $matches['path'];

        if (!$filesystem->exists(dirname($this->configPath()))) {
            $filesystem->makeDirectory(dirname($this->configPath()), 0755, true);
        }

        try {
            $config = $this->readConfig();
        } catch (RuntimeException $e) {
            $config = ['users' => []];
        }

        $config['users']["{$user}-{$provider}"] = [
            'path' => $homeDirectory,
            'token' => $token,
            'provider' => $provider,
        ];

        if ($url = $this->option('url')) {
            $config['users']["{$user}-{$provider}"]['url'] = $url;
        }

        $this->storeConfig($config);
    }
}
