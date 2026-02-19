/**
 * Тестовый скрипт для проверки работы Context7-mcp сервера
 * Демонстрирует использование инструментов query-docs и resolve-library-id
 */

// Эти функции будут реализованы MCP сервером
// Здесь приведены примеры использования

async function testQueryDocs() {
    console.log("🔍 Тестирование query-docs инструмента...");
    
    try {
        // Пример поиска документации
        const result1 = await query_docs({
            query: "Vue 3 Pinia store best practices",
            sources: ["vue-docs", "pinia-documentation"]
        });
        console.log("Результат поиска Vue/Pinia:", result1);
        
        // Пример поиска по проектной документации
        const result2 = await query_docs({
            query: "Maribulka calendar component implementation",
            sources: ["maribulka-architecture", "project-docs"]
        });
        console.log("Результат поиска по проекту:", result2);
        
        // Пример поиска технических решений
        const result3 = await query_docs({
            query: "TypeScript interface for booking system",
            sources: ["typescript-handbook", "best-practices"]
        });
        console.log("Результат поиска TypeScript:", result3);
        
    } catch (error) {
        console.error("❌ Ошибка при тестировании query-docs:", error);
    }
}

async function testResolveLibraryId() {
    console.log("📚 Тестирование resolve-library-id инструмента...");
    
    try {
        // Пример определения идентификаторов библиотек
        const lib1 = await resolve_library_id({
            library: "vue",
            version: "3.5"
        });
        console.log("Vue 3.5:", lib1);
        
        const lib2 = await resolve_library_id({
            library: "fullcalendar",
            version: "6.x"
        });
        console.log("FullCalendar 6.x:", lib2);
        
        const lib3 = await resolve_library_id({
            library: "@mdi/light-js",
            version: "latest"
        });
        console.log("@mdi/light-js:", lib3);
        
    } catch (error) {
        console.error("❌ Ошибка при тестировании resolve-library-id:", error);
    }
}

async function demonstrateUsage() {
    console.log("🧪 Демонстрация использования Context7 сервера");
    console.log("=============================================");
    
    // Тестирование поиска документации
    await testQueryDocs();
    
    console.log(""); // Пустая строка для разделения
    
    // Тестирование определения библиотек
    await testResolveLibraryId();
    
    console.log("");
    console.log("✅ Тестирование завершено!");
    console.log("Используйте эти инструменты в своих задачах разработки.");
}

// Функции-заглушки (будут заменены реальными MCP инструментами)
async function query_docs(params) {
    console.log(`query_docs вызван с параметрами:`, params);
    return {
        status: "mock",
        message: "Это демонстрационный ответ. Реальные данные будут доступны при подключении к Context7 серверу.",
        results: [
            { title: "Документ 1", url: "http://example.com/doc1", relevance: 0.95 },
            { title: "Документ 2", url: "http://example.com/doc2", relevance: 0.87 }
        ]
    };
}

async function resolve_library_id(params) {
    console.log(`resolve_library_id вызван с параметрами:`, params);
    return {
        status: "mock",
        message: "Это демонстрационный ответ. Реальные данные будут доступны при подключении к Context7 серверу.",
        library: params.library,
        version: params.version,
        id: `${params.library}@${params.version}`,
        documentation: `https://${params.library}.com/docs`
    };
}

// Запуск демонстрации
demonstrateUsage()
    .then(() => console.log("Демонстрация завершена"))
    .catch(error => console.error("Ошибка демонстрации:", error));