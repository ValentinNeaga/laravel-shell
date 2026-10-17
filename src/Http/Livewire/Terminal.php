<?php

namespace Jakyeru\LaravelShell\Http\Livewire;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Jakyeru\LaravelShell\Rules\CommandRule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Terminal extends Component
{
    /**
     * The current directory.
     *
     * @var string
     */
    #[Locked]
    public string $currentDirectory;

    /**
     * Authorize every request made to the component.
     */
    public function boot(): void
    {
        abort_unless(Gate::allows('viewLaravelShell'), 403);
    }

    /**
     * Mount the component.
     */
    #[Layout('laravel-shell::layouts.app')]
    public function mount(): void
    {
        $this->changeDirectory(base_path(), false);
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view('laravel-shell::livewire.terminal');
    }

    /**
     * Run a command.
     */
    public function runCommand(string $command): void
    {
        $command = trim($command);

        chdir($this->currentDirectory);

        if ($command === 'cd' || Str::startsWith($command, 'cd ')) {
            $target = trim(substr($command, 2));
            $changed = $this->changeDirectory($target === '' ? base_path() : $target);
            $this->audit($command, $changed ? 'executed' : 'failed', $changed ? 0 : 1);
            return;
        }

        $validator = validator(['command' => $command], [
            'command' => ['required', new CommandRule],
        ]);

        if ($validator->fails()) {
            $this->audit($command, 'rejected');
            $this->dispatch('laravel-shell:terminal-output', ['output' => [$validator->errors()->first()]]);
            return;
        }

        $output = [];
        $exitCode = null;
        $startedAt = hrtime(true);

        exec($this->shellCommand($command) . ' 2>&1', $output, $exitCode);

        $this->audit($command, 'executed', $exitCode, (hrtime(true) - $startedAt) / 1e6);

        $output = array_map('rtrim', $output);

        $this->dispatch('laravel-shell:terminal-output', ['output' => array_values($output)]);
    }

    /**
     * Change the current directory.
     */
    protected function changeDirectory(string $directory, bool $dispatch = true): bool
    {
        if (! is_dir($directory)) {
            $this->dispatch('laravel-shell:terminal-output', ['output' => [__('Directory does not exist.')]]);
            return false;
        }

        chdir($directory);

        $this->currentDirectory = str_replace('\\', '/', getcwd());

        if ($dispatch) {
            $this->dispatch('laravel-shell:directory-change', ['directory' => $this->currentDirectory]);
        }

        return true;
    }

    /**
     * Write the command to the audit log.
     */
    protected function audit(string $command, string $status, ?int $exitCode = null, ?float $duration = null): void
    {
        if (! config('laravel-shell.logging.enabled')) {
            return;
        }

        $request = request();

        Log::channel(config('laravel-shell.logging.channel'))->log($status === 'rejected' ? 'warning' : 'info', 'Laravel Shell command '.$status.'.', [
            'command' => $command,
            'directory' => $this->currentDirectory,
            'exit_code' => $exitCode,
            'duration_ms' => $duration === null ? null : round($duration, 2),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Create the shell command for the current operating system.
     **/
    protected function shellCommand(string $command): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe -Command ' . escapeshellarg($command);
        }

        return 'bash -c ' . escapeshellarg($command);
    }
}
