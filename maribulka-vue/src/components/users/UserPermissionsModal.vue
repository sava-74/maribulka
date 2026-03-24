<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiClose, mdiCheck, mdiCancel, mdiRefresh } from '@mdi/js'
import { hasPermission, type Role, type Section, type Action } from '../../stores/permissions'

interface User {
  id: number
  full_name: string | null
  login: string
  role: string
}

const props = defineProps<{ user: User }>()
const emit = defineEmits(['close'])

const isReadOnly = computed(() => ['admin', 'superuser'].includes(props.user.role))

const SECTIONS: Array<{ key: Section; label: string }> = [
  { key: 'calendar', label: 'Календарь' },
  { key: 'bookings', label: 'Записи' },
  { key: 'income', label: 'Доход' },
  { key: 'expenses', label: 'Расход' },
  { key: 'reports', label: 'Отчёты' },
  { key: 'clients', label: 'Клиенты' },
  { key: 'shooting_types', label: 'Типы съёмок' },
  { key: 'promotions', label: 'Акции' },
  { key: 'expense_categories', label: 'Категории расходов' },
  { key: 'settings', label: 'Настройки' },
  { key: 'users', label: 'Пользователи' },
]

const ACTIONS: Action[] = ['view', 'create', 'edit', 'delete']

const ACTION_LABELS: Record<Action, string> = {
  view: 'Просмотр',
  create: 'Создание',
  edit: 'Редактирование',
  delete: 'Удаление',
}

// Map: section -> action -> boolean | null (null = use role default)
const overrides = ref<Record<string, Record<string, boolean | null>>>({})

onMounted(async () => {
  const res = await fetch(`/api/permissions.php?action=get&user_id=${props.user.id}`, {
    credentials: 'include',
  })
  const data = await res.json()
  if (data.success) {
    for (const p of data.data ?? []) {
      if (!overrides.value[p.section]) overrides.value[p.section] = {}
      overrides.value[p.section]![p.action] = p.allowed
    }
  }
})

type CellState = 'default' | 'allow' | 'deny'

function getState(section: string, action: string): CellState {
  const val = overrides.value[section]?.[action]
  if (val === undefined || val === null) return 'default'
  return val ? 'allow' : 'deny'
}

function getDefaultIcon(section: Section, action: Action): string {
  const allowed = hasPermission(props.user.role as Role, section, action, [])
  return allowed ? mdiCheck : mdiCancel
}

async function toggle(section: Section, action: Action) {
  const current = getState(section, action)
  let next: CellState

  if (current === 'default') next = 'allow'
  else if (current === 'allow') next = 'deny'
  else next = 'default'

  if (!overrides.value[section]) overrides.value[section] = {}

  if (next === 'default') {
    // Remove the override
    try {
      await fetch('/api/permissions.php?action=delete', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: props.user.id, section, action }),
      })
      overrides.value[section][action] = null
    } catch {
      // revert — do nothing, state unchanged
    }
  } else {
    try {
      await fetch('/api/permissions.php?action=set', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: props.user.id, section, action, allowed: next === 'allow' }),
      })
      overrides.value[section][action] = next === 'allow'
    } catch {
      // revert
    }
  }
}

async function resetAll() {
  try {
    await fetch('/api/permissions.php?action=reset', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: props.user.id }),
    })
    overrides.value = {}
  } catch {
    // silent fail
  }
}
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay-main" @click.self="$emit('close')">
      <div class="padGlass">
        <div class="modal-glassTitle">Права: {{ user.full_name ?? user.login }}</div>

        <div v-if="isReadOnly" class="modal-message">Права этого пользователя не изменяемы</div>

        <table class="data-table">
          <thead>
            <tr>
              <th>Раздел</th>
              <th v-for="a in ACTIONS" :key="a">{{ ACTION_LABELS[a] }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in SECTIONS" :key="s.key">
              <td>{{ s.label }}</td>
              <td v-for="a in ACTIONS" :key="a">
                <button
                  class="btnGlass icon-only"
                  @click="!isReadOnly && toggle(s.key, a)"
                  :disabled="isReadOnly"
                  :title="getState(s.key, a)"
                >
                  <span class="inner-glow"></span>
                  <span class="top-shine"></span>
                  <svg-icon
                    type="mdi"
                    :path="getState(s.key, a) === 'allow' ? mdiCheck : getState(s.key, a) === 'deny' ? mdiCancel : getDefaultIcon(s.key, a)"
                    class="btn-icon"
                  />
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="ButtonFooter PosSpace">
          <button v-if="!isReadOnly" class="btnGlass iconText" @click="resetAll">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiRefresh" class="btn-icon" />
            <span>Сбросить всё</span>
          </button>
          <button class="btnGlass iconText" @click="$emit('close')">
            <span class="inner-glow"></span>
            <span class="top-shine"></span>
            <svg-icon type="mdi" :path="mdiClose" class="btn-icon" />
            <span>Закрыть</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
