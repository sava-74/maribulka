# 📘 Скрипты для работы с MCP памятью

## 🚀 1. Скрипт начала чата (start-chat.js)

```javascript
/**
 * Скрипт начала новой сессии работы
 * Восстанавливает контекст и готовит среду для новой сессии
 */
async function startChatSession() {
  console.log("🚀 Запуск новой сессии работы над проектом Maribulka");
  
  try {
    // 1. Получить список всех доступных контекстов
    console.log("📋 Получение списка контекстов...");
    const contextList = await context({ action: "list" });
    console.log("Доступные контексты:", contextList);

    // 2. Восстановить текущую задачу
    console.log("🎯 Восстановление текущей задачи...");
    const currentTask = await context({ action: "get", topic: "current-task" });
    console.log("Текущая задача:", currentTask);

    // 3. Получить статус проекта
    console.log("📊 Получение статуса проекта...");
    const projectStatus = await context({ action: "get", topic: "project-status" });
    console.log("Статус проекта:", projectStatus);

    // 4. Получить список всех записей в памяти
    console.log("💾 Получение записей из памяти...");
    const memoryList = await memory({ action: "list" });
    console.log("Доступные записи в памяти:", memoryList);

    // 5. Найти последние задачи
    console.log("🔍 Поиск текущих задач...");
    const currentTasks = await memory({ action: "search", query: "current-tasks" });
    console.log("Текущие задачи:", currentTasks);

    // 6. Получить критические правила
    console.log("🔐 Получение критических правил...");
    const commRule = await memory({ action: "retrieve", key: "communication-rule" });
    const critRules = await memory({ action: "retrieve", key: "critical-rules" });
    console.log("Правила общения:", commRule);
    console.log("Критические правила:", critRules);

    // 7. Установить контекст новой сессии
    await context({
      action: "add",
      topic: "session-start",
      content: `Сессия начата ${new Date().toISOString()}`
    });

    console.log("✅ Сессия успешно инициализирована");
    return { currentTask, projectStatus, currentTasks };

  } catch (error) {
    console.error("❌ Ошибка при запуске сессии:", error);
    throw error;
  }
}

// Вызов скрипта
startChatSession()
  .then(result => console.log("Результат инициализации:", result))
  .catch(error => console.error("Ошибка выполнения скрипта:", error));
```

## 🔄 2. Скрипт промежуточного сохранения (mid-session-save.js)

```javascript
/**
 * Скрипт промежуточного сохранения прогресса
 * Выполняется в процессе работы для сохранения текущего прогресса
 */
async function midSessionSave(progressDetails) {
  console.log("💾 Выполняется промежуточное сохранение прогресса...");

  try {
    // 1. Обновить текущую задачу
    await context({
      action: "add",
      topic: "current-development",
      content: progressDetails.currentDevelopment || "Продолжаем работу над проектом"
    });

    // 2. Сохранить текущий прогресс
    const timestamp = new Date().toISOString();
    await memory({
      action: "store",
      key: `progress-${timestamp}`,
      value: progressDetails.progressDescription || "Текущий прогресс",
      ttl: 86400 // 24 часа
    });

    // 3. Обновить статус задач
    if (progressDetails.taskStatus) {
      await memory({
        action: "store",
        key: "current-tasks",
        value: progressDetails.taskStatus,
        ttl: 0 // Без истечения
      });
    }

    // 4. Сохранить промежуточные результаты
    if (progressDetails.intermediateResults) {
      await memory({
        action: "store",
        key: `results-${timestamp}`,
        value: progressDetails.intermediateResults,
        ttl: 86400
      });
    }

    // 5. Обновить контекст сессии
    await context({
      action: "add",
      topic: "session-progress",
      content: `Прогресс сохранен в ${timestamp}`
    });

    console.log("✅ Промежуточное сохранение завершено");
    return { status: "success", timestamp };

  } catch (error) {
    console.error("❌ Ошибка при промежуточном сохранении:", error);
    throw error;
  }
}

// Пример вызова скрипта (можно вызывать многократно в процессе сессии)
/*
midSessionSave({
  currentDevelopment: "Работаем над мобильной адаптацией календаря",
  progressDescription: "Завершена адаптация основных компонентов, начинаем тестирование",
  taskStatus: "✅ Мобильная адаптация календаря - 70%, ⏳ Тестирование на устройствах - начать завтра",
  intermediateResults: "Реализованы media queries для мобильных устройств"
})
  .then(result => console.log("Результат сохранения:", result))
  .catch(error => console.error("Ошибка:", error));
*/
```

## 🏁 3. Скрипт завершения работы (end-session.js)

```javascript
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

console.log("📘 Скрипты для работы с MCP памятью успешно определены!");
console.log("💡 Используйте соответствующий скрипт в начале, в процессе и в конце вашей рабочей сессии.");