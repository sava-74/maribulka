// VSCode Memento Protocol Integration Script
// Автоматически запускается при открытии проекта в VSCode

const { exec } = require('child_process');
const fs = require('fs');

class VSCodeAutoIntegration {
    constructor() {
        this.projectRoot = 'D:/GitHub/maribulka';
        this.mementoPath = 'D:/GitHub/mcp-servers/memento-protocol';
        this.outputChannel = null;
    }

    async initialize() {
        console.log('🚀 Инициализация Memento Protocol для VSCode...');
        
        // Проверяем наличие необходимых файлов
        await this.checkRequirements();
        
        // Запускаем MCP серверы
        await this.startMCPServers();
        
        // Активируем хуки
        await this.setupHooks();
        
        console.log('✅ Memento Protocol готов к работе в VSCode');
    }

    async checkRequirements() {
        const requiredFiles = [
            `${this.projectRoot}/.vscode/mcp.json`,
            `${this.projectRoot}/.vscode/settings.json`,
            `${this.mementoPath}/scripts/memento-memory-recall.sh`
        ];

        for (const file of requiredFiles) {
            if (!fs.existsSync(file)) {
                throw new Error(`Отсутствует обязательный файл: ${file}`);
            }
        }
    }

    async startMCPServers() {
        console.log('🔧 Запуск MCP серверов...');
        
        const servers = [
            {
                name: 'Sequential-thinking',
                command: 'npx -y @modelcontextprotocol/server-sequential-thinking'
            },
            {
                name: 'Context7-mcp', 
                command: 'npx -y @upstash/context7-mcp@latest'
            },
            {
                name: 'Playwright-mcp',
                command: 'npx @playwright/mcp@latest'
            },
            {
                name: 'Memento-protocol',
                command: `node ${this.mementoPath}/src/index.js`
            }
        ];

        for (const server of servers) {
            try {
                exec(server.command, { cwd: this.projectRoot }, (error, stdout, stderr) => {
                    if (error) {
                        console.error(`❌ Ошибка запуска ${server.name}:`, error.message);
                    } else {
                        console.log(`✅ ${server.name} сервер запущен`);
                    }
                });
            } catch (error) {
                console.error(`❌ Не удалось запустить ${server.name}:`, error.message);
            }
        }
    }

    async setupHooks() {
        console.log('🔗 Настройка хуков для VSCode...');
        
        // Создаем наблюдателя за изменениями в редакторе
        setInterval(async () => {
            await this.checkForUserInput();
        }, 2000);
    }

    async checkForUserInput() {
        // Эмулируем проверку пользовательского ввода
        // В реальной реализации это будет через VSCode API
        const testQuery = "план на завтра";
        const memories = await this.simulateRecall(testQuery);
        
        if (memories.length > 0) {
            this.displayRecallResult(memories.length, memories);
        }
    }

    async simulateRecall(query) {
        // Симуляция вызова memento_recall
        return [
            {
                id: 'mem_test_1',
                content: 'план на завтра: переделать панель расходы по категориям',
                type: 'plan',
                tags: ['финансы', 'разработка']
            }
        ];
    }

    displayRecallResult(count, memories) {
        const message = `Memento Recall: ${count} воспоминаний`;
        console.log(`\n${message}`);
        
        memories.forEach(memory => {
            const tags = memory.tags ? ` [${memory.tags.join(', ')}]` : '';
            const content = memory.content.substring(0, 80);
            console.log(`  ${memory.id} (${memory.type})${tags} — ${content}`);
        });
    }
}

// Автозапуск при импорте модуля
const integration = new VSCodeAutoIntegration();
integration.initialize().catch(console.error);

module.exports = VSCodeAutoIntegration;