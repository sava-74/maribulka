// Windows-совместимая версия для реального вызова memento
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

class WindowsMementoTester {
    constructor() {
        this.scriptPath = 'D:/GitHub/mcp-servers/memento-protocol/scripts/memento-memory-recall.sh';
        this.gitBashPath = 'C:/Program Files/Git/bin/sh.exe'; // Путь к Git Bash
    }

    async findShell() {
        // Проверяем различные варианты shell
        const possibleShells = [
            this.gitBashPath,
            'C:/Program Files (x86)/Git/bin/sh.exe',
            'sh',
            'bash'
        ];

        for (const shell of possibleShells) {
            try {
                if (shell.includes('/') && fs.existsSync(shell)) {
                    return shell;
                } else if (shell === 'sh' || shell === 'bash') {
                    // Проверяем через which/command
                    const which = spawn(process.platform === 'win32' ? 'where' : 'which', [shell]);
                    let output = '';
                    
                    which.stdout.on('data', data => output += data.toString());
                    which.on('close', code => {
                        if (code === 0 && output.trim()) {
                            return shell;
                        }
                    });
                }
            } catch (e) {
                continue;
            }
        }
        
        throw new Error('Не найден подходящий shell (sh/bash) для выполнения скрипта');
    }

    async testMementoCall() {
        console.log('🔍 Поиск shell для выполнения memento-memory-recall.sh...');
        
        try {
            const shell = await this.findShell();
            console.log(`✅ Найден shell: ${shell}`);
            
            console.log('📤 Тестовый вызов с сообщением: "план на завтра"');
            
            return new Promise((resolve, reject) => {
                const child = spawn(shell, [this.scriptPath], {
                    cwd: path.dirname(this.scriptPath),
                    env: {
                        ...process.env,
                        MEMENTO_API_KEY: 'mp_live_a6f04585bef67482d0629359a4d41cd0',
                        MEMENTO_API_URL: 'https://memento-api.myrakrusemark.workers.dev',
                        MEMENTO_WORKSPACE: 'fathom'
                    }
                });

                const testData = JSON.stringify({ prompt: "план на завтра" });
                
                let stdout = '';
                let stderr = '';

                child.stdin.write(testData);
                child.stdin.end();

                child.stdout.on('data', (data) => {
                    stdout += data.toString();
                });

                child.stderr.on('data', (data) => {
                    stderr += data.toString();
                });

                child.on('close', (code) => {
                    console.log(`\n🏁 Shell завершен с кодом: ${code}`);
                    
                    if (stderr) {
                        console.log('⚠️  Ошибки shell:', stderr);
                    }
                    
                    if (stdout) {
                        console.log('📥 Результат:');
                        console.log('--- НАЧАЛО ВЫВОДА ---');
                        console.log(stdout);
                        console.log('--- КОНЕЦ ВЫВОДА ---');
                        
                        // Пытаемся распарсить как JSON
                        try {
                            const result = JSON.parse(stdout);
                            console.log('\n✅ Распознан JSON ответ:');
                            console.log(JSON.stringify(result, null, 2));
                        } catch (e) {
                            console.log('\n📄 Вывод не является валидным JSON');
                        }
                    } else {
                        console.log('❌ Пустой вывод - скрипт не вернул результатов');
                    }
                    
                    resolve({ code, stdout, stderr });
                });

                child.on('error', (error) => {
                    console.error('❌ Ошибка выполнения:', error.message);
                    reject(error);
                });
            });
            
        } catch (error) {
            console.error('❌ Ошибка поиска shell:', error.message);
            return { error: error.message };
        }
    }
}

// Запуск теста
const tester = new WindowsMementoTester();
tester.testMementoCall().then(result => {
    console.log('\n✅ Тест завершен');
    if (result.error) {
        console.log('Подробности ошибки:', result.error);
    }
}).catch(console.error);