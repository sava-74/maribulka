// Тест для memento-precompact-distill.sh
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

class PrecompactDistillTester {
    constructor() {
        this.scriptPath = 'D:/GitHub/mcp-servers/memento-protocol/scripts/memento-precompact-distill.sh';
        this.gitBashPath = 'C:/Program Files/Git/bin/sh.exe';
    }

    async testDistillWithSampleTranscript() {
        console.log('🔍 Тестирование memento-precompact-distill.sh');
        
        // Создаем тестовую расшифровку JSONL
        const sampleTranscript = [
            { role: "user", content: "Привет, как дела?" },
            { role: "assistant", content: "Привет! У меня всё хорошо, спасибо. Как ваши дела?" },
            { role: "user", content: "Планы на завтра включают работу над проектом Maribulka. Нужно добавить новые функции в панель учета расходов." },
            { role: "assistant", content: "Отлично! Завтра мы можем реализовать фильтрацию расходов по категориям и добавить графики аналитики. Также стоит улучшить систему уведомлений о превышении бюджета." },
            { role: "user", content: "Да, и не забудь про экспорт данных в Excel. Это важно для финансового отчета." },
            { role: "assistant", content: "Понял. Добавлю экспорт в Excel с возможностью выбора периода и категорий. Будет удобно для составления отчетов." }
        ];

        const jsonlContent = sampleTranscript.map(line => JSON.stringify(line)).join('\n');
        const transcriptPath = './test-transcript.jsonl';
        
        // Сохраняем тестовую расшифровку
        fs.writeFileSync(transcriptPath, jsonlContent);
        console.log(`✅ Создана тестовая расшифровка: ${transcriptPath}`);
        console.log(`Длина: ${jsonlContent.length} символов`);
        
        if (jsonlContent.length < 200) {
            console.log('⚠️  Расшифровка слишком короткая для теста (менее 200 символов)');
            return;
        }

        try {
            console.log('📤 Вызов memento-precompact-distill.sh...');
            
            return new Promise((resolve, reject) => {
                const child = spawn(this.gitBashPath, [this.scriptPath], {
                    cwd: path.dirname(this.scriptPath),
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
                        console.log('⚠️  STDERR:', stderr);
                    }
                    
                    if (stdout) {
                        console.log('📥 STDOUT:');
                        console.log('--- НАЧАЛО ---');
                        console.log(stdout);
                        console.log('--- КОНЕЦ ---');
                        
                        try {
                            const result = JSON.parse(stdout);
                            console.log('\n✅ Распознан JSON ответ:');
                            console.log(JSON.stringify(result, null, 2));
                        } catch (e) {
                            console.log('\n📄 Текстовый вывод (не JSON):', stdout);
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

                // Таймаут 30 секунд как в спецификации
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

    async directApiTest() {
        console.log('\n🔍 Прямой тест /v1/distill API');
        
        // Создаем тестовый контент для дистилляции
        const conversationText = `
Пользователь: Привет, как дела?
Ассистент: Привет! У меня всё хорошо, спасибо. Как ваши дела?

Пользователь: Планы на завтра включают работу над проектом Maribulka. Нужно добавить новые функции в панель учета расходов.

Ассистент: Отлично! Завтра мы можем реализовать фильтрацию расходов по категориям и добавить графики аналитики. Также стоит улучшить систему уведомлений о превышении бюджета.

Пользователь: Да, и не забудь про экспорт данных в Excel. Это важно для финансового отчета.

Ассистент: Понял. Добавлю экспорт в Excel с возможностью выбора периода и категорий. Будет удобно для составления отчетов.
`;

        const postData = JSON.stringify({
            content: conversationText,
            type: "conversation"
        });

        const https = require('https');
        const options = {
            hostname: 'memento-api.myrakrusemark.workers.dev',
            port: 443,
            path: '/v1/distill',
            method: 'POST',
            headers: {
                'Authorization': 'Bearer mp_live_a6f04585bef67482d0629359a4d41cd0',
                'Content-Type': 'application/json',
                'Content-Length': Buffer.byteLength(postData)
            }
        };

        return new Promise((resolve, reject) => {
            const req = https.request(options, (res) => {
                let data = '';
                
                res.on('data', (chunk) => {
                    data += chunk;
                });
                
                res.on('end', () => {
                    console.log(`\n🏁 HTTP статус distill: ${res.statusCode}`);
                    console.log('📥 Ответ distill API:');
                    console.log('--- НАЧАЛО ---');
                    console.log(data);
                    console.log('--- КОНЕЦ ---');
                    
                    try {
                        const result = JSON.parse(data);
                        console.log('\n✅ Распознанный JSON distill:');
                        console.log(JSON.stringify(result, null, 2));
                    } catch (e) {
                        console.log('\n❌ Невалидный JSON ответ от distill');
                    }
                    
                    resolve({ statusCode: res.statusCode, data });
                });
            });

            req.on('error', (error) => {
                console.error('❌ Ошибка distill запроса:', error.message);
                reject(error);
            });

            req.write(postData);
            req.end();
        });
    }
}

// Запуск тестов
async function runTests() {
    const tester = new PrecompactDistillTester();
    
    console.log('=== ТЕСТИРОВАНИЕ MEMENTO-PRECOMPACT-DISTILL.SH ===\n');
    
    try {
        // Тест 1: Прямой вызов API
        await tester.directApiTest();
        
        console.log('\n' + '='.repeat(50) + '\n');
        
        // Тест 2: Реальный вызов скрипта
        await tester.testDistillWithSampleTranscript();
        
        console.log('\n✅ Все тесты завершены');
    } catch (error) {
        console.error('❌ Ошибка во время тестирования:', error.message);
    }
}

runTests();