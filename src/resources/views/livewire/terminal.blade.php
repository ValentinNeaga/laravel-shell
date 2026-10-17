<div>
    <div wire:ignore id="terminal"></div>
    <script>
        let terminalEnabled = false;
        let command = '';
        let directory = '{{ $currentDirectory }}';

        let commandHistory = [];
        let commandHistoryIndex = -1;

        document.addEventListener('DOMContentLoaded', () => {
            const terminal = new window.Terminal({
                fontFamily: '"Cascadia Code", Menlo, monospace',
                theme: {
                    foreground: '{{ config('laravel-shell.terminal.colors.foreground') }}',
                    background: '{{ config('laravel-shell.terminal.colors.background') }}',
                    cursorAccent: '{{ config('laravel-shell.terminal.colors.cursorAccent') }}',
                    selection: '{{ config('laravel-shell.terminal.colors.selection') }}',
                    black: '{{ config('laravel-shell.terminal.colors.black') }}',
                    brightBlack: '{{ config('laravel-shell.terminal.colors.brightBlack') }}',
                    red: '{{ config('laravel-shell.terminal.colors.red') }}',
                    brightRed: '{{ config('laravel-shell.terminal.colors.brightRed') }}',
                    green: '{{ config('laravel-shell.terminal.colors.green') }}',
                    brightGreen: '{{ config('laravel-shell.terminal.colors.brightGreen') }}',
                    yellow: '{{ config('laravel-shell.terminal.colors.yellow') }}',
                    brightYellow: '{{ config('laravel-shell.terminal.colors.brightYellow') }}',
                    blue: '{{ config('laravel-shell.terminal.colors.blue') }}',
                    brightBlue: '{{ config('laravel-shell.terminal.colors.brightBlue') }}',
                    magenta: '{{ config('laravel-shell.terminal.colors.magenta') }}',
                    brightMagenta: '{{ config('laravel-shell.terminal.colors.brightMagenta') }}',
                    cyan: '{{ config('laravel-shell.terminal.colors.cyan') }}',
                    brightCyan: '{{ config('laravel-shell.terminal.colors.brightCyan') }}',
                    white: '{{ config('laravel-shell.terminal.colors.white') }}',
                    brightWhite: '{{ config('laravel-shell.terminal.colors.brightWhite') }}',
                },
                cursorBlink: '{{ config('laravel-shell.terminal.cursorBlink') }}',
                cols: Math.floor(window.innerWidth / 9),
                rows: Math.floor(window.innerHeight / 19),
            });

            terminal.open(document.getElementById('terminal'));

            @if (config('laravel-shell.terminal.startup'))
                @foreach(config('laravel-shell.terminal.startup', []) as $line)
                    terminal.writeln('{{ $line }}');
                @endforeach
            @endif

            @if(config('laravel-shell.terminal.showInteractiveWarning'))
                terminal.writeln('\x1b[31;1mWarning:\x1b[0m Running interactive commands \x1b[31;1mwill hang your server\x1b[0m indefinitely until restarted.\r\n')
            @endif

            terminal.onData(e => {
                if (!terminalEnabled) return false;
                switch (e) {
                    case '\u0003': // Ctrl+C
                        terminal.write('^C');
                        prompt(terminal);
                        break;
                    case '\r': // Enter
                        if (command.trim().length > 0) {
                            terminalEnabled = false;
                        }
                        runCommand(terminal, command);
                        command = '';
                        break;
                    case '\u007F': // Backspace (DEL)
                        // Do not delete the prompt
                        if (terminal._core.buffer.x > directory.length + 1 + {{ strlen(config('laravel-shell.terminal.prompt', '$ ')) }}) {
                            terminal.write('\b \b');
                            command = command.substring(0, command.length - 1);
                        }
                        break;
                    case '\u001B\u005B\u0041': // Up arrow
                        // Write the previous command in the history
                        if (commandHistoryIndex < commandHistory.length - 1) {
                            commandHistoryIndex++;
                            command = commandHistory[commandHistoryIndex];
                            terminal.write(`\r${directory} {{ config('laravel-shell.terminal.prompt', '$') }}` + command + '\x1b[0K');
                        }
                        break;
                    case '\u001B\u005B\u0042': // Down arrow
                        // Write the next command in the history
                        if (commandHistoryIndex >= 0) {
                            commandHistoryIndex--;
                            if (commandHistoryIndex === -1) {
                                command = '';
                                terminal.write(`\r${directory} {{ config('laravel-shell.terminal.prompt', '$') }}` + '\x1b[0K');
                            } else {
                                command = commandHistory[commandHistoryIndex];
                                terminal.write(`\r${directory} {{ config('laravel-shell.terminal.prompt', '$') }}` + command + '\x1b[0K');
                            }
                        }
                        break;
                    default:
                        if (e >= String.fromCharCode(0x20) && e <= String.fromCharCode(0x7E) || e >= '\u00a0') {
                            command += e;
                            terminal.write(e);
                        }
                }
            });

            prompt(terminal);

            window.addEventListener('resize', () => {
                terminal.resize(Math.floor(window.innerWidth / 9), Math.floor(window.innerHeight / 19));
            });

            Livewire.on('laravel-shell:terminal-output', event => {
                if (typeof event[0].output === 'string') {
                    terminal.writeln(event[0].output);
                } else if (Array.isArray(event[0].output)) {
                    event[0].output.forEach(line => {
                        terminal.writeln(line);
                    })
                }

                prompt(terminal);
            });

            Livewire.on('laravel-shell:directory-change', event => {
                directory = event[0].directory;
                prompt(terminal);
            });
        });

        function prompt(terminal) {
            terminal.write(`\r\n${directory} {{ config('laravel-shell.terminal.prompt', '$') }}`);
            terminalEnabled = true;
            terminal.focus();
        }

        function runCommand(terminal, text) {
            const command = text.trim()

            if (command.length > 0) {
                terminal.writeln('');

                commandHistoryIndex = -1; // Reset command history index

                const commandIndex = commandHistory.indexOf(command); // Get index of command in history
                if (commandIndex > -1) { // Check if command already exists in history
                    commandHistory.splice(commandIndex, 1); // Remove command from current position in history
                }
                commandHistory.unshift(command); // Add command to beginning of history

                @this.runCommand(text);
            } else {
                prompt(terminal);
            }
        }
    </script>
</div>