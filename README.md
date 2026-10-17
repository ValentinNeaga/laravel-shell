<p align="center">
    <a href="https://github.com/JakyeRU/Laravel-Shell" target="_blank">
        <img src="https://raw.githubusercontent.com/JakyeRU/Laravel-Shell/main/.github/Laravel-Shell-transparent.png" height=200>
    </a>
</p>

<p align="center">
    <img src="https://img.shields.io/github/v/release/jakyeru/laravel-shell?logo=github&color=blue&style=for-the-badge" alt="release">
    <img src="https://img.shields.io/packagist/dt/jakyeru/laravel-shell?logo=packagist&style=for-the-badge" alt="downloads">
</p>

# About Laravel Shell
> [!IMPORTANT]
> Laravel-Shell is not a full-fledged terminal interface and its functionality is limited to running shell commands via the PHP exec function. As a result, it may not be suitable for running interactive shell processes. However, this package was designed specifically to aid administrators on shared servers that do not have SSH access, but it can be utilized by anyone. Keep this in mind when deciding if this package meets your needs.

Laravel-Shell is a package for Laravel applications that adds a terminal interface to your web application. This allows you to run shell commands directly from your browser. With "Laravel-Shell", you can access the terminal from anywhere, at any time, and execute commands on the fly.

The package integrates seamlessly into Laravel, and the terminal interface is intuitive and user-friendly. You can run a wide range of commands, including those related to database management, file management, and more.

# Installation
You can install the package via composer:

```bash
composer require jakyeru/laravel-shell
```
After installing you can navigate to `/laravel-shell` to access the terminal.

To customize the package, publish the configuration file:
```bash
php artisan vendor:publish --provider="Jakyeru\LaravelShell\LaravelShellServiceProvider" --tag="config"
```

# Security
> [!CAUTION]
> Laravel-Shell executes arbitrary shell commands as the web server user. Restrict access before enabling it in any environment other than `local`.

## Authorization
Access to the terminal is controlled by the `viewLaravelShell` gate. By default, the gate grants access only when the application environment is `local`. All other requests receive a `403` response.

To grant access in other environments, define the gate in the `boot` method of your `App\Providers\AppServiceProvider`:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('viewLaravelShell', function (User $user) {
        return in_array($user->email, [
            'admin@example.com',
        ]);
    });
}
```

The gate is checked on the initial page request and on every subsequent Livewire request made by the terminal. A gate with a non-nullable `$user` parameter denies all guests.

## Middleware
The `route.middleware` option defines the middleware applied to the terminal routes. To require authentication and a recent password confirmation, add the `auth` and `password.confirm` middleware:

```php
'route' => [
    'prefix' => 'laravel-shell',
    'middleware' => ['web', 'auth', 'password.confirm'],
],
```

The `password.confirm` middleware requires the application to define a `password.confirm` route, which is provided by Laravel's starter kits.

## Command Policies
The `commands` option defines which commands may be executed. Patterns support the `*` wildcard.

```php
'commands' => [
    'allowed' => [
        'php artisan *',
        'composer install*',
        'git pull',
    ],
    'blocked' => [
        'php artisan tinker*',
        'php artisan serve*',
        'php artisan queue:work*',
        'php artisan queue:listen*',
        'php artisan schedule:work*',
    ],
],
```

- `allowed`: When not empty, a command is executed only if it matches at least one pattern. Commands containing shell operators (`;`, `&`, `|`, `<`, `>`, `` ` ``, `$(` or line breaks) are rejected.
- `blocked`: A command is rejected if any segment separated by a shell operator matches a pattern. The default list rejects long-running commands that would block the PHP process.

The `blocked` list is not a security boundary, since a blocked command can be invoked through other means, such as a script or an alias. Use the `allowed` list to restrict execution.

The `cd` command is handled by the package and is not subject to command policies.

## Audit Logging
Every command is written to the application log with the authenticated user ID, IP address, user agent, working directory, exit code and duration. Rejected commands are logged with the `warning` level.

```php
'logging' => [
    'enabled' => env('LARAVEL_SHELL_LOGGING', true),
    'channel' => env('LARAVEL_SHELL_LOG_CHANNEL'),
],
```

When `channel` is `null`, the application's default log channel is used. To write the audit log to a dedicated file, define a channel in `config/logging.php` and set `LARAVEL_SHELL_LOG_CHANNEL` to its name.

## Disabling the Terminal
Set `LARAVEL_SHELL_ENABLED=false` to unregister the routes, views and components of the package.

# Version Compatibility
> [!NOTE]
> Laravel-Shell follows [Laravel's Support Policy](https://laravel.com/docs/master/releases).

| Laravel Version | Laravel-Shell Version | PHP Version | Active Support | Security Fixes |
|:---------------:|:---------------------:|:-----------:|:--------------:|:--------------:|
|       13        |        ^4.0.0         |  8.3 - 8.5  |       ✅        |       ✅      |
|       12        |        ^3.0.0         |  8.2 - 8.5  |       ❌        |       ❌      |
|       11        |        ^2.0.0         |  8.2 - 8.4  |       ❌        |       ❌      |
|       10        |        ^1.2.0         |  8.1 - 8.2  |       ❌        |       ❌      |
|        9        |     1.0.0 - 1.1.1     |     8.0     |       ❌        |       ❌      |

# Screenshots
<p align="center">
    <img src="https://raw.githubusercontent.com/JakyeRU/Laravel-Shell/main/.github/Screenshot_2023-02-05_192731.png" height=200><br>
    <img src="https://raw.githubusercontent.com/JakyeRU/Laravel-Shell/main/.github/Screenshot_2023-02-05_193258.png" height=200>
</p>


# Contributing
Thank you for considering contributing to Laravel-Shell! You can read the contribution guide [here](https://github.com/JakyeRU/Laravel-Shell/blob/main/.github/CONTRIBUTING.md).

# Code of Conduct
In order to ensure that the community is welcoming to everyone, please review and abide by the [Code of Conduct](https://github.com/JakyeRU/Laravel-Shell/blob/main/.github/CODE_OF_CONDUCT.md).

# Security Vulnerabilities
If you need to report a security vulnerability please read our [Security Policy](https://github.com/JakyeRU/Laravel-Shell/blob/main/.github/SECURITY.md).

# License
Laravel-Shell is open-sourced software licensed under the [MIT](https://github.com/JakyeRU/Laravel-Shell/blob/main/LICENSE) license.

If you encounter any other error(s), please open an issue on [GitHub](https://github.com/JakyeRU/Laravel-Shell/issues/new/choose).
