<?php

namespace Jakyeru\LaravelShell\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class CommandRule implements ValidationRule
{
    /**
     * The shell operators rejected when an allowlist is configured.
     *
     * @var array<string>
     */
    protected array $operators = [';', '&', '|', '>', '<', '`', '$(', "\n", "\r"];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail(__('The command cannot be empty.'));
            return;
        }

        $command = $this->normalize($value);
        $allowed = config('laravel-shell.commands.allowed', []);

        if (! empty($allowed)) {
            if (Str::contains($value, $this->operators)) {
                $fail(__('Shell operators are not allowed.'));
                return;
            }

            if (! Str::is($allowed, $command)) {
                $fail(__('This command is not allowed to be run.'));
                return;
            }
        }

        $blocked = config('laravel-shell.commands.blocked', []);

        foreach ($this->segments($value) as $segment) {
            if (! empty($blocked) && Str::is($blocked, $segment)) {
                $fail(__('This command is not allowed to be run.'));
                return;
            }
        }
    }

    /**
     * Collapse whitespace in the given command.
     */
    protected function normalize(string $command): string
    {
        return trim(preg_replace('/\s+/', ' ', $command));
    }

    /**
     * Split the given command into segments separated by shell operators.
     *
     * @return array<string>
     */
    protected function segments(string $command): array
    {
        $segments = preg_split('/&&|\|\||[;&|`\r\n]|\$\(|\(|\)/', $command);

        return array_values(array_filter(array_map([$this, 'normalize'], $segments), fn (string $segment) => $segment !== ''));
    }
}
