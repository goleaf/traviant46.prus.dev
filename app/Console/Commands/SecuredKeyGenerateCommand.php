<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\KeyGenerateCommand as BaseKeyGenerateCommand;
use Symfony\Component\Console\Command\Command;

class SecuredKeyGenerateCommand extends BaseKeyGenerateCommand
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isLocked = (config('security.app_key.locked', true) === true);
        $hasKey = (string) config('app.key') !== '';

        if ($isLocked && $hasKey && ! $this->option('show') && ! $this->option('force')) {
            $this->components->info('Application key generation is locked; keeping existing APP_KEY value.');

            return Command::SUCCESS;
        }

        parent::handle();

        return Command::SUCCESS;
    }
}

