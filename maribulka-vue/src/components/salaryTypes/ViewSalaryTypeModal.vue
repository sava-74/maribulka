<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiClose } from '@mdi/js'

interface SalaryType {
  id: number
  title: string
  monthly_salary: number
  salary_value: number
  percentage_of_the_order: number
  the_percentage_value: number
  interest_dividends: number
  value_dividend_percentages: number
  fixed_order: number
  fixed_value_order: number
}

const props = defineProps<{ salaryType: SalaryType }>()
const emit = defineEmits(['close'])
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay-main">
      <div class="padGlass modal-sm">
        <div class="modal-glassTitle">{{ salaryType.title }}</div>

        <div class="info-row">
          <span class="info-label">Оклад в месяц:</span>
          <span class="info-value">{{ salaryType.monthly_salary ? `${salaryType.salary_value} руб` : '—' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Процент от заказа:</span>
          <span class="info-value">{{ salaryType.percentage_of_the_order ? `${salaryType.the_percentage_value}%` : '—' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Проценты дивидендов:</span>
          <span class="info-value">{{ salaryType.interest_dividends ? `${salaryType.value_dividend_percentages}%` : '—' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Фиксированное от заказа:</span>
          <span class="info-value">{{ salaryType.fixed_order ? `${salaryType.fixed_value_order} руб` : '—' }}</span>
        </div>

        <div class="ButtonFooter PosCenter">
          <button class="btnGlass iconText" @click="emit('close')">
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
