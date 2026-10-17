<div>
    <div wire:ignore id="terminal"></div>
    <script>
        let terminalEnabled = false;
        let command = '';
        let pendingInput = '';
        let directory = @js($currentDirectory);

        const promptSymbol = @js(config('laravel-shell.terminal.prompt', '$ '));

        let commandHistory = [];
        let commandHistoryIndex = -1;

        const interactiveCommands = [
            'php artisan tinker',
            'php artisan serve',
            'php artisan queue:work',
            'php artisan queue:listen',
            'php artisan schedule:work',
        ];

        document.addEventListener('DOMContentLoaded', () => {
            const terminal = new window.Terminal({
                fontFamily: '"Cascadia Code", Menlo, monospace',
                theme: @js(config('laravel-shell.terminal.colors', [])),
                cursorBlink: @js((bool) config('laravel-shell.terminal.cursorBlink')),
                cols: Math.floor(window.innerWidth / 9),
                rows: Math.floor(window.innerHeight / 19),
            });

            terminal.open(document.getElementById('terminal'));

            @if (config('laravel-shell.terminal.startup'))
                @foreach(config('laravel-shell.terminal.startup', []) as $line)
                    terminal.writeln(@js(stripcslashes($line)));
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
                        command = '';
                        pendingInput = '';
                        prompt(terminal);
                        break;
                    case '\u001B[A': // Up arrow
                        showHistory(terminal, 1);
                        break;
                    case '\u001B[B': // Down arrow
                        showHistory(terminal, -1);
                        break;
                    default:
                        if (!e.startsWith('\u001B')) {
                            handleInput(terminal, e);
                        }
                }
            });

            prompt(terminal);

            window.addEventListener('resize', () => {
                terminal.resize(Math.floor(window.innerWidth / 9), Math.floor(window.innerHeight / 19));
            });
        });

        function promptText() {
            return `${directory} ${promptSymbol}`;
        }

        function prompt(terminal) {
            terminal.write(`\r\n${promptText()}`);
            terminalEnabled = true;
            terminal.focus();

            if (pendingInput !== '') {
                const input = pendingInput;
                pendingInput = '';
                handleInput(terminal, input);
            }
        }

        function handleInput(terminal, data) {
            const characters = Array.from(data);

            for (let index = 0; index < characters.length; index++) {
                const character = characters[index];
                const code = character.codePointAt(0);

                if (character === '\r' || character === '\n') {
                    if (character === '\r' && characters[index + 1] === '\n') {
                        index++;
                    }

                    const text = command;
                    command = '';
                    pendingInput = characters.slice(index + 1).join('');
                    runCommand(terminal, text);
                    return;
                }

                if (character === '\u007F' || character === '\b') {
                    if (command.length > 0) {
                        replaceCommand(terminal, Array.from(command).slice(0, -1).join(''));
                    }
                } else if (code >= 0x20 && (code < 0x7F || code >= 0xA0)) {
                    command += character;
                    terminal.write(character);
                }
            }
        }

        function replaceCommand(terminal, text) {
            const length = Array.from(promptText() + command).length;
            const rows = length > 0 ? Math.floor((length - 1) / terminal.cols) : 0;

            terminal.write('\r' + (rows > 0 ? `\x1b[${rows}A` : '') + '\x1b[0J' + promptText() + text);
            command = text;
        }

        function showHistory(terminal, direction) {
            const index = commandHistoryIndex + direction;

            if (index < -1 || index >= commandHistory.length) {
                return;
            }

            commandHistoryIndex = index;
            replaceCommand(terminal, index === -1 ? '' : commandHistory[index]);
        }

        function runCommand(terminal, text) {
            const command = text.trim()

            if (command.length > 0) {
                terminal.writeln('');

                if (interactiveCommands.includes(command)) {
                    terminal.writeln(`\x1b[31;1mError:\x1b[0m Running interactive commands \x1b[31;1mwill hang your server\x1b[0m indefinitely until restarted.`);
                    prompt(terminal);
                    return false;
                }

                commandHistoryIndex = -1; // Reset command history index

                const commandIndex = commandHistory.indexOf(command); // Get index of command in history
                if (commandIndex > -1) { // Check if command already exists in history
                    commandHistory.splice(commandIndex, 1); // Remove command from current position in history
                }
                commandHistory.unshift(command); // Add command to beginning of history

                terminalEnabled = false;

                @this.runCommand(command)
                    .then(result => {
                        directory = result.directory;
                        result.output.forEach(line => terminal.writeln(line));
                    })
                    .catch(() => {
                        terminal.writeln('\x1b[31;1mError:\x1b[0m The command could not be completed. Check the application logs for details.');
                    })
                    .finally(() => prompt(terminal));
            } else {
                prompt(terminal);
            }
        }
    </script>
</div>
