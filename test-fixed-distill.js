// Тест для исправленного memento-precompact-distill.sh
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

class FixedPrecompactDistillTester {
    constructor() {
        this.scriptPath = './fixed-memento-precompact-distill.sh';
        this.gitBashPath = 'C:/Program Files/Git/bin/sh.exe';
    }

    async testFixedDistill() {
        console.log('🔍 Тестирование ИСПРАВЛЕННОГО memento-precompact-distill.sh');
        
        // Создаем тестовую расшифровку JSONL
        const sampleTranscript = [
            { role: "user", content: "Привет, как дела?" },
            { role: "assistant", content: "Привет! У меня всё хорошо. Работаю над проектом Maribulka." },
            { role: "user", content: "Планы на завтра включают добавление фильтрации расходов по категориям и экспорт в Excel." },
            { role: "assistant", content: "Отлично! Завершим панель учета расходов с графиками аналитики и системой уведомлений." },
            { role: "user", content: "Также нужно реализовать авторизацию пользователей и настройки профиля." },
            { role: "assistant", content: "Понял. Добавлю систему регистрации, входа и персональные настройки для каждого пользователя." }
        ];

        const jsonlContent = sampleTranscript.map(line => JSON.stringify(line)).join('\n');
        const transcriptPath = './test-fixed-transcript.jsonl';
        
        // Сохраняем тестовую расшифровку
        fs.writeFileSync(transcriptPath, jsonlContent);
        console.log(`✅ Создана тестовая расшифровка: ${transcriptPath}`);
        console.log(`Длина: ${jsonlContent.length} символов`);
        
        if (jsonlContent.length < 200) {
            console.log('⚠️  Расшифровка слишком короткая для теста');
            return;
        }

        try {
            console.log('📤 Вызов исправленного скрипта...');
            
            return new Promise((resolve, reject) => {
                const child = spawn(this.gitBashPath, [this.scriptPath], {
                    cwd: process.cwd(),
                    env: {
                        ...process.env,
                        MEMENTO_API_KEY: 'mp_live_a6f04585bef67482d0629359a4d41cd0',
                        MEMENTO_API_URL: 'https://memento-api.myrakrusemark.workers.dev',
                        MEMENTO_WORKSPACE: 'fathom',
                        TRANSCRIPT_FILE: path.resolve(transcriptPath)
                    }
                });

                let stdout = '';
                let stderr = '';

                // Отправляем путь к файлу через stdin
                child.stdin.write(transcriptPath);
                child.stdin.end();

                child.stdout.on('data', (data) => {
                    stdout += data.toString();
                });

                child.stderr.on('data', (data) => {
                    stderr += data.toString();
                });

                child.on('close', (code) => {
                    console.log(`\n🏁 Скрипт завершен с кодом: ${code}`);
                    
                    if (stderr) {
                        console.log('⚠️  STDERR (DEBUG информация):');
                        console.log(stderr);
                    }
                    
                    if (stdout) {
                        console.log('📥 STDOUT (результат):');
                        console.log('--- НАЧАЛО ---');
                        console.log(stdout);
                        console.log('--- КОНЕЦ ---');
                        
                        if (stdout.includes('Memento Distill:')) {
                            console.log('✅ УСПЕШНО: Найдено сообщение о дистилляции!');
                        }
                    } else {
                        console.log('❌ Пустой вывод - скрипт не вернул результатов');
                    }
                    
                    // Очищаем временный файл
                    if (fs.existsSync(transcriptPath)) {
                        fs.unlinkSync(transcriptPath);
                        console.log('🗑️  Временный файл удален');
                    }
                    
                    resolve({ code, stdout, stderr });
                });

                child.on('error', (error) => {
                    console.error('❌ Ошибка запуска:', error.message);
                    // Очищаем временный файл при ошибке
                    if (fs.existsSync(transcriptPath)) {
                        fs.unlinkSync(transcriptPath);
                    }
                    reject(error);
                });

                // Таймаут 30 секунд
                setTimeout(() => {
                    child.kill();
                    console.log('⏰ Таймаут 30 секунд истек');
                    if (fs.existsSync(transcriptPath)) {
                        fs.unlinkSync(transcriptPath);
                    }
                    resolve({ timeout: true });
                }, 30000);
            });
            
        } catch (error) {
            console.error('❌ Ошибка тестирования:', error.message);
            // Очищаем временный файл при ошибке
            if (fs.existsSync(transcriptPath)) {
                fs.unlinkSync(transcriptPath);
            }
            return { error: error.message };
        }
    }
}

// Запуск теста
async function runFixedTest() {
    const tester = new FixedPrecompactDistillTester();
    
    console.log('=== ТЕСТИРОВАНИЕ ИСПРАВЛЕННОГО MEMENTO-PRECOMPACT-DISTILL.SH ===\n');
    
    try {
        await tester.testFixedDistill();
        console.log('\n✅ Тест исправленного скрипта завершен');
    } catch (error) {
        console.error('❌ Ошибка тестирования исправленного скрипта:', error.message);
    }
}

runFixedTest();