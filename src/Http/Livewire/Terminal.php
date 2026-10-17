<?php

namespace Jakyeru\LaravelShell\Http\Livewire;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Jakyeru\LaravelShell\Rules\CommandRule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\Process\ExecutableFinder;

class Terminal extends Component
{
    /**
     * The current directory.
     *
     * @var string
     */
    public string $currentDirectory;

    /**
     * The previous directory.
     *
     * @var string
     */
    public string $previousDirectory;

    /**
     * Mount the component.
     */
    #[Layout('laravel-shell::layouts.app')]
    public function mount(): void
    {
        $this->currentDirectory = $this->previousDirectory = $this->normalizePath(base_path());
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
     *
     * @return array{output: array<int, string>, directory: string}
     */
    public function runCommand(string $command): array
    {
        $command = trim($command);

        if (! is_dir($this->currentDirectory)) {
            $missingDirectory = $this->currentDirectory;

            $this->changeDirectory(base_path());

            return $this->response([
                __('The directory :directory no longer exists. Changed directory to :base.', [
                    'directory' => $missingDirectory,
                    'base' => $this->currentDirectory,
                ]),
            ]);
        }

        $validator = validator(['command' => $command], [
            'command' => ['required', new CommandRule],
        ]);

        if ($validator->fails()) {
            return $this->response([$validator->errors()->first()]);
        }

        if ($command === 'cd') {
            $this->changeDirectory(base_path());

            return $this->response([]);
        }

        $marker = '__LARAVEL_SHELL_'.Str::random(32).'__';
        $arguments = $this->processArguments($command, $marker);
        $timeout = (int) config('laravel-shell.timeout', 60);
        $output = '';
        $timedOut = false;

        $process = Process::path($this->currentDirectory)
            ->timeout($timeout)
            ->env(['OLDPWD' => $this->previousDirectory])
            ->start($arguments, function (string $type, string $buffer) use (&$output) {
                $output .= $buffer;
            });

        $pid = $process->id();

        try {
            $process->wait();
        } catch (ProcessTimedOutException) {
            $timedOut = true;

            if ($pid && basename($arguments[0]) === 'setsid') {
                $this->killProcessGroup($pid);
            }
        }

        if (($position = strrpos($output, $marker)) !== false) {
            $remainder = explode("\n", ltrim(substr($output, $position + strlen($marker)), "\r\n"), 2);
            $directory = trim($remainder[0]);

            $output = substr($output, 0, $position).($remainder[1] ?? '');

            if ($directory !== '' && is_dir($directory)) {
                $this->changeDirectory($directory);
            }
        }

        $lines = $this->outputLines($output);

        if ($timedOut) {
            $lines[] = "\x1b[31;1mError:\x1b[0m ".__('The command exceeded the timeout of :seconds seconds and was terminated.', ['seconds' => $timeout]);
        }

        return $this->response($lines);
    }

    /**
     * Change the current directory.
     */
    protected function changeDirectory(string $directory): void
    {
        $directory = $this->normalizePath($directory);

        if ($directory !== $this->currentDirectory) {
            $this->previousDirectory = $this->currentDirectory;
            $this->currentDirectory = $directory;
        }
    }

    /**
     * Normalize the given directory path.
     */
    protected function normalizePath(string $directory): string
    {
        return str_replace('\\', '/', realpath($directory) ?: $directory);
    }

    /**
     * Create the response sent to the terminal.
     *
     * @param  array<int, string>  $output
     * @return array{output: array<int, string>, directory: string}
     */
    protected function response(array $output): array
    {
        return [
            'output' => $output,
            'directory' => $this->currentDirectory,
        ];
    }

    /**
     * Split the process output into lines.
     *
     * @return array<int, string>
     */
    protected function outputLines(string $output): array
    {
        $output = rtrim(str_replace("\r\n", "\n", $output));

        return $output === '' ? [] : array_map('rtrim', explode("\n", $output));
    }

    /**
     * Create the process arguments for the configured shell.
     *
     * @return array<int, string>
     */
    protected function processArguments(string $command, string $marker): array
    {
        $shell = config('laravel-shell.shell') ?: $this->defaultShell();

        if (preg_match('/^(powershell|pwsh)(\.exe)?$/i', basename($shell))) {
            $script = implode("\n", [
                $command,
                "Write-Output ''",
                "Write-Output '{$marker}'",
                '(Get-Location).Path',
            ]);

            return [$shell, '-NoProfile', '-NonInteractive', '-EncodedCommand', base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'))];
        }

        $script = implode("\n", [
            'exec 2>&1',
            $command,
            "printf '\\n%s\\n' '{$marker}'",
            'pwd',
        ]);

        $arguments = [$shell, '-c', $script];

        if ($setsid = (new ExecutableFinder)->find('setsid', null, ['/usr/bin', '/bin'])) {
            array_unshift($arguments, $setsid);
        }

        return $arguments;
    }

    /**
     * Get the default shell for the current operating system.
     */
    protected function defaultShell(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';
        }

        return (new ExecutableFinder)->find('bash', '/bin/sh', ['/bin', '/usr/bin', '/usr/local/bin']);
    }

    /**
     * Kill every process in the given process group.
     */
    protected function killProcessGroup(int $pid): void
    {
        if (function_exists('posix_kill')) {
            posix_kill(-$pid, 9);

            return;
        }

        Process::run(['kill', '-KILL', '--', '-'.$pid]);
    }
}
