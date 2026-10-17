<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel Shell Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether Laravel Shell is enabled or not.
    |
    */

    'enabled' => env('LARAVEL_SHELL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the route that will be used to access Laravel
    | Shell.
    |
    */

    'route' => [
        'prefix' => 'laravel-shell',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Command Policies
    |--------------------------------------------------------------------------
    |
    | The "allowed" list restricts execution to commands matching at least one
    | of the given patterns. When the list is not empty, shell operators such
    | as ";", "&&", "|", ">", "`" and "$(" are rejected. An empty list allows
    | any command. The "blocked" list rejects any command segment matching one
    | of the given patterns. Patterns support the "*" wildcard.
    |
    */

    'commands' => [
        'allowed' => [],
        'blocked' => [
            'php artisan tinker*',
            'php artisan serve*',
            'php artisan queue:work*',
            'php artisan queue:listen*',
            'php artisan schedule:work*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, every command is written to the given log channel along
    | with the user, IP address, working directory, exit code and duration.
    | A null channel uses the application's default log channel.
    |
    */

    'logging' => [
        'enabled' => env('LARAVEL_SHELL_LOGGING', true),
        'channel' => env('LARAVEL_SHELL_LOG_CHANNEL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Terminal Customization
    |--------------------------------------------------------------------------
    |
    | Here you may configure how the terminal will look.
    |
    */

    'terminal' => [
        'colors' => [
            'foreground' => '#eff0eb',
            'background' => '#282a36',
            'cursorAccent' => '#282a36',
            'selection' => '#97979b33',
            'black' => '#282a36',
            'brightBlack' => '#686868',
            'red' => '#890301',
            'brightRed' => '#ff5c57',
            'green' => '#5af78e',
            'brightGreen' => '#5af78e',
            'yellow' => '#f3f99d',
            'brightYellow' => '#f3f99d',
            'blue' => '#57c7ff',
            'brightBlue' => '#57c7ff',
            'magenta' => '#ff6ac1',
            'brightMagenta' => '#ff6ac1',
            'cyan' => '#9aedfe',
            'brightCyan' => '#9aedfe',
            'white' => '#f1f1f0',
            'brightWhite' => '#eff0eb',
        ],
        'startup' => [
            'Welcome to \x1b[34;1mLaravel Shell\x1b[0m \x1b[93;1mv' . \Jakyeru\LaravelShell\LaravelShellServiceProvider::VERSION . '\x1b[0m!',
            'Running Laravel \x1b[93;1mv' . Illuminate\Foundation\Application::VERSION . '\x1b[0m (PHP \x1b[93;1mv' . PHP_VERSION . '\x1b[0m)\r\n',
        ],
        'showInteractiveWarning' => true,
        'cursorBlink' => true,
        'prompt' => '$ ',
    ],

];
