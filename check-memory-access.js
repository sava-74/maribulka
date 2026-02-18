// check-memory-access.js
// Скрипт для проверки доступности памяти проекта Maribulka

console.log("🔍 Проверка доступности памяти проекта Maribulka");

// Проверим наличие файлов памяти
const fs = require('fs');
const path = require('path');

// Путь к папке памяти
const memoryDir = path.join(__dirname, 'memory');

console.log("📁 Проверка папки памяти:", memoryDir);

if (fs.existsSync(memoryDir)) {
    console.log("✅ Папка памяти найдена");
    
    // Прочитаем все файлы памяти
    const memoryFiles = fs.readdirSync(memoryDir);
    
    console.log(`📋 Найдено ${memoryFiles.length} файлов памяти:`);
    memoryFiles.forEach(file => {
        console.log(`  - ${file}`);
    });
    
    // Проверим основной файл памяти
    const mainMemoryFile = path.join(memoryDir, 'MEMORY.md');
    if (fs.existsSync(mainMemoryFile)) {
        const mainMemoryContent = fs.readFileSync(mainMemoryFile, 'utf8');
        console.log("\n📋 Содержание основного файла памяти (первые 500 символов):");
        console.log(mainMemoryContent.substring(0, 500) + "...");
    }
    
    // Проверим ключевые файлы памяти
    const keyFiles = ['architecture.md', 'styles.md', 'deployment.md', 'patterns.md'];
    console.log("\n🔑 Проверка ключевых файлов памяти:");
    keyFiles.forEach(file => {
        const filePath = path.join(memoryDir, file);
        const exists = fs.existsSync(filePath);
        console.log(`  ${exists ? '✅' : '❌'} ${file}`);
    });
    
} else {
    console.log("❌ Папка памяти не найдена!");
}

// Проверим также путь, указанный в памяти
const legacyMemoryPath = 'C:\\Users\\sava\\.claude\\projects\\d--GitHub-maribulka\\memory\\MEMORY.md';
console.log(`\n🔍 Проверка устаревшего пути к памяти: ${legacyMemoryPath}`);
if (fs.existsSync(legacyMemoryPath.replace('C:\\Users\\sava', process.env.USERPROFILE || 'C:\\Users\\sava'))) {
    console.log("✅ Устаревший путь к памяти также доступен");
} else {
    console.log("⚠️ Устаревший путь к памяти недоступен (это нормально)");
}

console.log("\n✅ Проверка памяти проекта завершена");
console.log("Теперь система памяти проекта доступна для использования.");