/**
 * Pinia Store для управления сессионной памятью в проекте Maribulka
 * Для использования в Vue 3 приложении
 */

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useSessionMemoryStore = defineStore('sessionMemory', () => {
    // Состояние
    const contextData = ref(new Map());
    const isLoading = ref(false);
    const lastCleanup = ref(Date.now());

    // Вычисляемые свойства
    const activeTopics = computed(() => {
        return Array.from(contextData.value.keys()).filter(key => {
            const entry = contextData.value.get(key);
            return !_isExpired(entry);
        });
    });

    const expiredCount = computed(() => {
        let count = 0;
        contextData.value.forEach(entry => {
            if (_isExpired(entry)) count++;
        });
        return count;
    });

    // Методы
    function saveContext(topic, content, options = {}) {
        const entry = {
            topic,
            content,
            timestamp: Date.now(),
            ttl: options.ttl || 3600,
            priority: options.priority || 'standard',
            tags: options.tags || []
        };

        contextData.value.set(topic, entry);
        
        // Сохраняем в localStorage для персистентности
        _persistToLocalStorage();
        
        return entry;
    }

    function getContext(topic) {
        const entry = contextData.value.get(topic);
        if (!entry) return null;
        
        if (_isExpired(entry)) {
            contextData.value.delete(topic);
            _persistToLocalStorage();
            return null;
        }
        
        return entry.content;
    }

    function removeContext(topic) {
        const result = contextData.value.delete(topic);
        if (result) {
            _persistToLocalStorage();
        }
        return result;
    }

    function searchContext(query) {
        const results = [];
        const searchTerm = query.toLowerCase();
        
        contextData.value.forEach((entry, topic) => {
            if (!_isExpired(entry)) {
                const contentStr = JSON.stringify(entry.content).toLowerCase();
                const topicStr = topic.toLowerCase();
                
                if (contentStr.includes(searchTerm) || topicStr.includes(searchTerm)) {
                    results.push({
                        topic,
                        content: entry.content,
                        timestamp: entry.timestamp,
                        priority: entry.priority,
                        tags: entry.tags
                    });
                }
            }
        });
        
        return results.sort((a, b) => {
            // Сортировка по приоритету и времени
            const priorityOrder = { critical: 3, important: 2, standard: 1 };
            if (a.priority !== b.priority) {
                return (priorityOrder[b.priority] || 0) - (priorityOrder[a.priority] || 0);
            }
            return b.timestamp - a.timestamp;
        });
    }

    function cleanupExpired() {
        let removedCount = 0;
        const now = Date.now();
        
        contextData.value.forEach((entry, topic) => {
            if (_isExpired(entry)) {
                contextData.value.delete(topic);
                removedCount++;
            }
        });
        
        if (removedCount > 0) {
            _persistToLocalStorage();
        }
        
        lastCleanup.value = now;
        return removedCount;
    }

    function loadFromStorage() {
        try {
            const stored = localStorage.getItem('maribulka-session-memory');
            if (stored) {
                const parsed = JSON.parse(stored);
                contextData.value = new Map(Object.entries(parsed));
                
                // Удаляем устаревшие записи при загрузке
                cleanupExpired();
            }
        } catch (error) {
            console.warn('Ошибка загрузки сессионной памяти:', error);
        }
    }

    function clearAll() {
        contextData.value.clear();
        localStorage.removeItem('maribulka-session-memory');
    }

    function exportContext() {
        const exportData = {};
        contextData.value.forEach((entry, topic) => {
            if (!_isExpired(entry)) {
                exportData[topic] = entry.content;
            }
        });
        return exportData;
    }

    function importContext(data, options = {}) {
        let importedCount = 0;
        const overwrite = options.overwrite !== false; // true по умолчанию
        
        Object.entries(data).forEach(([topic, content]) => {
            if (overwrite || !contextData.value.has(topic)) {
                saveContext(topic, content, {
                    ttl: options.ttl,
                    priority: options.priority
                });
                importedCount++;
            }
        });
        
        return importedCount;
    }

    // Приватные методы
    function _persistToLocalStorage() {
        try {
            const serializableData = Object.fromEntries(contextData.value);
            localStorage.setItem('maribulka-session-memory', JSON.stringify(serializableData));
        } catch (error) {
            console.warn('Ошибка сохранения сессионной памяти:', error);
        }
    }

    function _isExpired(entry) {
        const now = Date.now();
        const expiryTime = entry.timestamp + (entry.ttl * 1000);
        return now > expiryTime;
    }

    // Автоматическая очистка каждые 10 минут
    setInterval(() => {
        if (Date.now() - lastCleanup.value > 600000) { // 10 минут
            cleanupExpired();
        }
    }, 300000); // Проверяем каждые 5 минут

    // Инициализация
    loadFromStorage();

    return {
        // Состояние
        contextData,
        isLoading,
        lastCleanup,
        
        // Вычисляемые свойства
        activeTopics,
        expiredCount,
        
        // Методы
        saveContext,
        getContext,
        removeContext,
        searchContext,
        cleanupExpired,
        clearAll,
        exportContext,
        importContext
    };
});

// Вспомогательные функции
export function useSessionContext(topic) {
    const store = useSessionMemoryStore();
    
    const content = computed({
        get: () => store.getContext(topic),
        set: (value) => store.saveContext(topic, value)
    });
    
    return {
        content,
        save: (data, options) => store.saveContext(topic, data, options),
        remove: () => store.removeContext(topic),
        exists: computed(() => store.contextData.has(topic) && 
                     !_isExpired(store.contextData.get(topic)))
    };
}

// Пример использования:
/*
// В компоненте Vue:
import { useSessionMemoryStore, useSessionContext } from '@/stores/session-memory';

export default {
  setup() {
    const sessionStore = useSessionMemoryStore();
    const currentTask = useSessionContext('current-development-task');
    
    // Сохранение контекста
    sessionStore.saveContext('current-task', {
      task: 'Реализация вкладки расходов',
      status: 'in-progress',
      startTime: new Date().toISOString()
    }, { priority: 'important', ttl: 7200 });
    
    // Использование реактивного контекста
    currentTask.content.value = { step: 1, description: 'Создание компонентов' };
    
    return {
      sessionStore,
      currentTask
    };
  }
};
*/