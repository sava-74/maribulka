// check-mcp-servers.js
// Скрипт для проверки запущенных MCP серверов

console.log("🔍 Проверка запущенных MCP серверов...");

// Проверим через системные команды
const { execSync } = require('child_process');
const os = require('os');

function checkProcesses() {
    console.log("📋 Проверка процессов в системе:");
    
    try {
        // Проверим все процессы, связанные с MCP
        const result = execSync('wmic process where "CommandLine like \'%@modelcontextprotocol%\' or CommandLine like \'%@upstash/context7-mcp%\' or CommandLine like \'%@playwright/mcp%\' or CommandLine like \'%memento%\'" get Name,ProcessId,CommandLine', { encoding: 'utf8' });
        
        console.log(result);
        
        // Проверим количество найденных процессов
        const lines = result.split('\n').filter(line => line.trim() !== '');
        const actualProcesses = lines.filter(line => !line.includes('Name'));
        
        console.log(`\n📊 Найдено MCP процессов: ${actualProcesses.length}`);
        
        if (actualProcesses.length > 0) {
            console.log("✅ MCP серверы активны");
        } else {
            console.log("⚠️ MCP серверы не найдены");
        }
        
    } catch (error) {
        console.log("❌ Ошибка при проверке процессов:", error.message);
    }
}

function checkNetworkPorts() {
    console.log("\n🌐 Проверка сетевых портов:");
    
    const portsToCheck = [
        { port: 3000, service: "Development server" },
        { port: 5173, service: "Vite dev server" }
    ];
    
    portsToCheck.forEach(({ port, service }) => {
        try {
            const result = execSync(`netstat -an | findstr :${port}`, { encoding: 'utf8' });
            if (result.includes(`:${port}`)) {
                console.log(`✅ ${service} (${port}) - активен`);
            } else {
                console.log(`❌ ${service} (${port}) - не активен`);
            }
        } catch (error) {
            console.log(`❌ ${service} (${port}) - ошибка проверки`);
        }
    });
}

function main() {
    console.log("🚀 Диагностика MCP серверов проекта Maribulka");
    console.log("============================================");
    
    checkProcesses();
    checkNetworkPorts();
    
    console.log("\n📋 Активные MCP серверы:");
    console.log("1. Sequential-thinking сервер (@modelcontextprotocol/server-sequential-thinking)");
    console.log("2. Context7-mcp сервер (@upstash/context7-mcp)");
    console.log("3. Playwright-mcp сервер (@playwright/mcp)");
    console.log("4. Memento-protocol сервер (долговременная память)");
    console.log("\n💡 Для запуска всех серверов используйте:");
    console.log("   Windows: .\\start-all-mcp-servers.ps1");
    console.log("   Linux/Mac: ./start-all-mcp-servers.sh");
}

main();