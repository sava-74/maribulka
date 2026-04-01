// src/stores/permissions.ts
// Дефолтные права по ролям. Секции и actions совпадают с api/permissions.php

export type Role = number
export type Section =
  | 'calendar' | 'bookings' | 'income' | 'expenses' | 'reports'
  | 'clients' | 'shooting_types' | 'promotions' | 'expense_categories' | 'salary_types'
  | 'users' | 'settings' | 'home' | 'news' | 'portfolio' | 'logs'
export type Action = 'view' | 'create' | 'edit' | 'delete'

// FULL = все 4 действия разрешены
// VIEW = только view
// NO   = ничего
type AccessLevel = 'FULL' | 'VIEW' | 'NO'

// id=1 СисАдмин, id=2 Директор, id=3 Руководитель, id=4 Администратор, id=5 Работник, id=6 Пользователь
export const ROLE_DEFAULTS: Record<number, Record<Section, AccessLevel>> = {
  1: {
    calendar: 'FULL', bookings: 'FULL', income: 'FULL', expenses: 'FULL',
    reports: 'FULL', clients: 'FULL', shooting_types: 'FULL', promotions: 'FULL',
    expense_categories: 'FULL', salary_types: 'FULL', users: 'FULL', settings: 'FULL',
    home: 'FULL', news: 'FULL', portfolio: 'FULL', logs: 'FULL',
  },
  2: {
    calendar: 'FULL', bookings: 'FULL', income: 'FULL', expenses: 'FULL',
    reports: 'FULL', clients: 'FULL', shooting_types: 'FULL', promotions: 'FULL',
    expense_categories: 'FULL', salary_types: 'FULL', users: 'FULL', settings: 'FULL',
    home: 'FULL', news: 'FULL', portfolio: 'FULL', logs: 'NO',
  },
  3: {
    calendar: 'FULL', bookings: 'FULL', income: 'NO', expenses: 'NO',
    reports: 'NO', clients: 'FULL', shooting_types: 'VIEW', promotions: 'VIEW',
    expense_categories: 'NO', salary_types: 'NO', users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'FULL', portfolio: 'VIEW', logs: 'NO',
  },
  4: {
    calendar: 'FULL', bookings: 'FULL', income: 'NO', expenses: 'NO',
    reports: 'NO', clients: 'FULL', shooting_types: 'VIEW', promotions: 'VIEW',
    expense_categories: 'NO', salary_types: 'NO', users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'FULL', portfolio: 'VIEW', logs: 'NO',
  },
  5: {
    calendar: 'VIEW', bookings: 'VIEW', income: 'NO', expenses: 'NO',
    reports: 'NO', clients: 'NO', shooting_types: 'NO', promotions: 'NO',
    expense_categories: 'NO', salary_types: 'NO', users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'VIEW', portfolio: 'VIEW', logs: 'NO',
  },
  6: {
    calendar: 'NO', bookings: 'NO', income: 'NO', expenses: 'NO',
    reports: 'NO', clients: 'NO', shooting_types: 'NO', promotions: 'NO',
    expense_categories: 'NO', salary_types: 'NO', users: 'NO', settings: 'NO',
    home: 'VIEW', news: 'VIEW', portfolio: 'VIEW', logs: 'NO',
  },
}

// Проверка права с учётом индивидуальных переопределений
export function hasPermission(
  role: Role,
  section: Section,
  action: Action,
  overrides: Array<{ section: string; action: string; allowed: boolean }>
): boolean {
  // 1. Индивидуальное переопределение
  const override = overrides.find(p => p.section === section && p.action === action)
  if (override !== undefined) return override.allowed

  // 2. Дефолт роли
  const level = ROLE_DEFAULTS[role]?.[section] ?? 'NO'
  if (level === 'FULL') return true
  if (level === 'NO') return false
  // VIEW = только просмотр
  return action === 'view'
}
