<?php

namespace App\Console\Commands;

use Core\ErrorHandler;
use Core\Jobs\Launcher;
use Illuminate\Console\Command;
use Throwable;

class GameEngineCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'game:engine
        {--runtime=300 : Number of seconds to keep the engine loop alive.}
        {--sleep=1 : Seconds to wait between health checks.}';

    /**
     * The console command description.
     */
    protected $description = 'Run the Travian game automation engine with health monitoring and logging.';

    /**
     * Indicates whether the command experienced a fatal failure during execution.
     */
    protected bool $hasFailure = false;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!function_exists('pcntl_fork')) {
            $this->error('The pcntl extension is required to run the game engine.');
            return Command::FAILURE;
        }

        $this->prepareEnvironment();
        $this->info('Bootstrapping the Travian game engine.');

        try {
            $this->bootstrapGame();
        } catch (Throwable $exception) {
            $this->logException('Failed to bootstrap the game engine', $exception);
            return Command::FAILURE;
        }

        $runtime = max(0, (int) $this->option('runtime'));
        $sleep = max(1, (int) $this->option('sleep'));
        $deadline = $runtime > 0 ? time() + $runtime : null;

        $logPath = $this->logPath();
        $healthPath = $this->healthPath();

        $this->writeLog($logPath, 'Game engine started with runtime=' . $runtime . 's and sleep=' . $sleep . 's.');

        $this->registerSignalHandlers($logPath, $healthPath);
        $this->launchJobs();

        $iteration = 0;
        global $loop;
        while ($loop) {
            ++$iteration;
            $start = microtime(true);

            $this->monitorChildProcesses($logPath);
            $this->updateHealth($healthPath, 'running', $iteration, null);

            if ($deadline !== null && time() >= $deadline) {
                $this->writeLog($logPath, 'Runtime limit reached. Preparing to shutdown.');
                break;
            }

            $duration = max(0, microtime(true) - $start);
            $this->writeLog($logPath, sprintf('Heartbeat #%d completed in %.2fms.', $iteration, $duration * 1000));

            sleep($sleep);
            pcntl_signal_dispatch();
        }

        $this->shutdownChildren($logPath);
        $this->updateHealth($healthPath, $this->hasFailure ? 'degraded' : 'stopped', $iteration, null);
        $this->writeLog($logPath, 'Game engine terminated.');

        return $this->hasFailure ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Prepare directories used for logs and health reports.
     */
    protected function prepareEnvironment(): void
    {
        $logDir = dirname($this->logPath());
        $healthDir = dirname($this->healthPath());

        foreach ([$logDir, $healthDir] as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
        }

        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
        }

        set_time_limit(0);
        ini_set('mysql.connect_timeout', '0');
        ini_set('max_execution_time', '0');
    }

    /**
     * Load the Travian bootstrap so the game services are available.
     */
    protected function bootstrapGame(): void
    {
        static $bootstrapped = false;
        if ($bootstrapped) {
            return;
        }

        $basePath = dirname(__DIR__, 3);
        require_once $basePath . '/main_script/include/bootstrap.php';
        $bootstrapped = true;
    }

    /**
     * Launch the long-running game jobs.
     */
    protected function launchJobs(): void
    {
        global $PIDs, $loop;
        $PIDs = [];
        $loop = true;

        try {
            Launcher::lunchJobs();
        } catch (Throwable $exception) {
            $this->logException('Failed to launch automation jobs', $exception);
            throw $exception;
        }
    }

    /**
     * Monitor child processes and report any unexpected exits.
     */
    protected function monitorChildProcesses(string $logPath): void
    {
        global $PIDs, $loop;
        foreach ($PIDs as $name => $pid) {
            $status = 0;
            $result = pcntl_waitpid($pid, $status, WNOHANG);
            if ($result === 0) {
                continue;
            }

            unset($PIDs[$name]);
            $message = sprintf('Worker "%s" (pid: %d) exited with status %d.', $name, $pid, $status);
            $this->writeLog($logPath, $message);
            $this->hasFailure = true;
            $loop = false;
            break;
        }
    }

    /**
     * Gracefully terminate remaining child processes.
     */
    protected function shutdownChildren(string $logPath): void
    {
        global $PIDs, $loop;
        $loop = false;
        foreach ($PIDs as $name => $pid) {
            if ($pid <= 0) {
                continue;
            }

            posix_kill($pid, SIGTERM);
            $this->writeLog($logPath, sprintf('Sent SIGTERM to worker "%s" (pid: %d).', $name, $pid));
            pcntl_waitpid($pid, $status);
        }
    }

    /**
     * Register signal handlers to ensure graceful shutdowns.
     */
    protected function registerSignalHandlers(string $logPath, string $healthPath): void
    {
        global $loop, $PIDs;
        $handler = function (int $signal) use (&$loop, &$PIDs, $logPath, $healthPath): void {
            $names = [
                SIGTERM => 'SIGTERM',
                SIGINT => 'SIGINT',
                SIGHUP => 'SIGHUP',
            ];
            $this->writeLog($logPath, sprintf('Received %s. Initiating shutdown.', $names[$signal] ?? (string) $signal));
            $this->updateHealth($healthPath, 'stopping', null, $names[$signal] ?? (string) $signal);
            $loop = false;
            foreach ($PIDs as $pid) {
                if ($pid > 0) {
                    posix_kill($pid, $signal);
                }
            }
        };

        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
        pcntl_signal(SIGHUP, $handler);
    }

    /**
     * Persist health information to disk.
     */
    protected function updateHealth(string $healthPath, string $status, ?int $iteration, ?string $reason): void
    {
        global $PIDs;

        $payload = [
            'status' => $status,
            'timestamp' => date(DATE_ATOM),
            'iteration' => $iteration,
            'active_workers' => array_map('intval', array_values($PIDs)),
            'reason' => $reason,
            'has_failure' => $this->hasFailure,
        ];

        file_put_contents($healthPath, json_encode($payload, JSON_PRETTY_PRINT), LOCK_EX);
    }

    /**
     * Write a message to the game engine log file and to the console output.
     */
    protected function writeLog(string $logPath, string $message): void
    {
        $line = sprintf('[%s] %s', date(DATE_ATOM), $message);
        file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        $this->line($line);
    }

    /**
     * Log an exception with stack trace information.
     */
    protected function logException(string $context, Throwable $exception): void
    {
        $message = $context . ': ' . $exception->getMessage();
        $this->error($message);
        ErrorHandler::getInstance()->handleExceptions($exception);
        $this->hasFailure = true;
        $this->writeLog($this->logPath(), $message);
    }

    /**
     * Determine the location of the health file.
     */
    protected function healthPath(): string
    {
        $basePath = dirname(__DIR__, 3);
        return $basePath . '/storage/app/game-engine-health.json';
    }

    /**
     * Determine the location of the log file.
     */
    protected function logPath(): string
    {
        $basePath = dirname(__DIR__, 3);
        return $basePath . '/storage/logs/game-engine.log';
    }
}
