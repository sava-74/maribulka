/**
 * Конфигурация временной зоны приложения
 * TODO: В будущем заменить на настройки из меню пользователя
 */

// Смещение от UTC в часах (Екатеринбург = UTC+5)
// NOTE: Сейчас не используется, т.к. берём локальную дату из браузера
export const TIMEZONE_OFFSET = 5

/**
 * Получить текущую локальную дату в формате YYYY-MM-DD
 * Использует локальную дату из браузера (getFullYear/getMonth/getDate)
 */
export function getLocalDateString(): string {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}
