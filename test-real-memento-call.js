// Тестовый скрипт для проверки реального вызова memento-memory-recall.sh
const { spawn } = require('child_process');
const fs = require('fs');

async function testRealMementoCall() {
    const scriptPath = 'D:/GitHub/mcp-servers/memento-protocol/scripts/memento-memory-recall.sh';
    
    if (!fs.existsSync(scriptPath)) {
        console.error('❌ Скрипт не найден:', scriptPath);
        return;
    }
    
    console.log('🔍 Тестирование реального вызова memento-memory-recall.sh');
    console.log('📤 Отправка тестового сообщения: "план на завтра"');
    
    return new Promise((resolve, reject) => {
        // Используем WSL или Git Bash если доступен
        const child = spawn('sh', [scriptPath], {
            cwd: 'D:/GitHub/mcp-servers/memento-protocol/scripts',
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
            console.log(`\n🏁 Процесс завершен с кодом: ${code}`);
            if (stdout) {
                console.log('📥 STDOUT:', stdout);
            }
            if (stderr) {
                console.log('⚠️  STDERR:', stderr);
            }
            
            if (code === 0 && stdout) {
                try {
                    const result = JSON.parse(stdout);
                    console.log('✅ УСПЕШНЫЙ JSON ответ:');
                    console.log(JSON.stringify(result, null, 2));
                } catch (e) {
                    console.log('📄 Текстовый ответ:', stdout);
                }
            }
            
            resolve({ code, stdout, stderr });
        });

        child.on('error', (error) => {
            console.error('❌ Ошибка запуска:', error.message);
            reject(error);
        });
    });
}

testRealMementoCall().then(() => {
    console.log('\n✅ Тест завершен');
}).catch(console.error);