/**
 * Скрипт завершения сессии работы
 * Сохраняет итоги, фиксирует результаты и планирует следующую сессию
 */
async function endSession(sessionSummary) {
  console.log("🏁 Завершение сессии работы...");

  try {
    // 1. Сохранить итоги сессии
    const endTime = new Date().toISOString();
    await context({
      action: "add",
      topic: "session-summary",
      content: sessionSummary.completedWork || `Сессия завершена ${endTime}`
    });

    // 2. Записать результаты работы
    await memory({
      action: "store",
      key: `session-results-${endTime.split('T')[0]}`,
      value: sessionSummary.results || "Результаты текущей сессии",
      ttl: 0 // Без истечения
    });

    // 3. Обновить статус задач
    if (sessionSummary.updatedTasks) {
      await memory({
        action: "store",
        key: "current-tasks",
        value: sessionSummary.updatedTasks,
        ttl: 0
      });
    }

    // 4. Запланировать следующую сессию
    if (sessionSummary.nextSessionPlan) {
      await context({
        action: "add",
        topic: "next-session-plan",
        content: sessionSummary.nextSessionPlan
      });
    }

    // 5. Зафиксировать достижения
    if (sessionSummary.achievements) {
      await memory({
        action: "store",
        key: `achievements-${endTime.split('T')[0]}`,
        value: sessionSummary.achievements,
        ttl: 0
      });
    }

    // 6. Завершить контекст сессии
    await context({
      action: "add",
      topic: "session-end",
      content: `Сессия завершена успешно в ${endTime}`
    });

    console.log("✅ Сессия успешно завершена");
    return { status: "completed", endTime };

  } catch (error) {
    console.error("❌ Ошибка при завершении сессии:", error);
    throw error;
  }
}

// Функция-заглушка для имитации MCP инструментов в случае если они недоступны
async function context(actionObj) {
  console.log(`context(${JSON.stringify(actionObj)}) - MCP сервер недоступен или не настроен`);
  return { status: "mock", data: actionObj };
}

async function memory(actionObj) {
  console.log(`memory(${JSON.stringify(actionObj)}) - MCP сервер недоступен или не настроен`);
  return { status: "mock", data: actionObj };
}

// Пример вызова скрипта
/*
endSession({
  completedWork: "Мобильная адаптация календаря завершена на 90%",
  results: "Реализована адаптивная сетка, добавлены touch события, протестировано на 3 устройствах",
  updatedTasks: "✅ Мобильная адаптация календаря - 90%, ⏳ Тестирование на реальных устройствах - завершено, ⏳ Оптимизация производительности - начать завтра",
  nextSessionPlan: "Завтра: оптимизация производительности, исправление мелких UI багов",
  achievements: "Добавлены media queries, реализован touch-интерфейс, улучшена отзывчивость UI"
})
  .then(result => console.log("Сессия завершена:", result))
  .catch(error => console.error("Ошибка завершения:", error));
*/

// Если файл запускается напрямую, выполним пример
if (require.main === module) {
  console.log("Запуск скрипта завершения сессии...");
  // Для демонстрации используем заглушку
  console.log("context и memory - функции-заглушки, так как MCP серверы не настроены");
  console.log("Для реального использования подключите MCP серверы");
}