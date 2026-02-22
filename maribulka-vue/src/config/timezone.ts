/**
 * Конфигурация временной зоны приложения
 * TODO: В будущем заменить на настройки из меню пользователя
 */

// Смещение от UTC в часах (Екатеринбург = UTC+5)
export const TIMEZONE_OFFSET = 5

/**
 * Получить текущую локальную дату в формате YYYY-MM-DD
 * с учётом настроенного часового пояса
 */
export function getLocalDateString(): string {
  const now = new Date()
  const utcTime = now.getTime() + now.getTimezoneOffset() * 60000
  const localTime = new Date(utcTime + TIMEZONE_OFFSET * 3600000)

  const year = localTime.getUTCFullYear()
  const month = String(localTime.getUTCMonth() + 1).padStart(2, '0')
  const day = String(localTime.getUTCDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}
