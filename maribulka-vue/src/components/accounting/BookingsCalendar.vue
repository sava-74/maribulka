<script setup lang="ts">
import { onMounted } from 'vue'
import { useBookingsStore } from '../../stores/bookings'

const bookingsStore = useBookingsStore()

onMounted(() => {
  bookingsStore.fetchBookings()
})

function getStatusColor(status: string) {
  switch (status) {
    case 'new': return '#60a5fa'
    case 'completed': return '#fbbf24'
    case 'delivered': return '#4ade80'
    case 'cancelled': return '#f87171'
    default: return '#9ca3af'
  }
}

function getStatusText(status: string) {
  switch (status) {
    case 'new': return '🟡 Новая'
    case 'completed': return '🟠 Состоялась'
    case 'delivered': return '🟢 Сдана'
    case 'cancelled': return '🔴 Отменена'
    default: return status
  }
}

function getPaymentStatusText(status: string) {
  switch (status) {
    case 'unpaid': return '🔴 Не оплачено'
    case 'partially_paid': return '🟡 Частично'
    case 'fully_paid': return '🟢 Оплачено'
    default: return status
  }
}
</script>

<template>
  <div class="bookings-calendar">
    <h2>📅 Записи на съёмку</h2>
    <p class="info">Календарь (таблица записей за текущий месяц)</p>

    <table>
      <thead>
        <tr>
          <th>Дата съёмки</th>
          <th>Клиент</th>
          <th>Телефон</th>
          <th>Тип съёмки</th>
          <th>Кол-во</th>
          <th>Сумма</th>
          <th>Оплачено</th>
          <th>Статус оплаты</th>
          <th>Статус записи</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="booking in bookingsStore.bookings" :key="booking.id">
          <td>{{ booking.shooting_date }}</td>
          <td>{{ booking.client_name }}</td>
          <td>{{ booking.phone }}</td>
          <td>{{ booking.shooting_type_name }}</td>
          <td>{{ booking.quantity }}</td>
          <td class="amount">{{ parseFloat(booking.total_amount).toFixed(2) }} ₽</td>
          <td class="amount-small">{{ parseFloat(booking.paid_amount).toFixed(2) }} ₽</td>
          <td>{{ getPaymentStatusText(booking.payment_status) }}</td>
          <td>
            <span class="status-badge" :style="{ backgroundColor: getStatusColor(booking.status) }">
              {{ getStatusText(booking.status) }}
            </span>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="bookingsStore.bookings.length === 0" class="empty">
      <p>📭 Нет записей за текущий месяц</p>
    </div>
  </div>
</template>

<style scoped>
.bookings-calendar h2 {
  margin: 0 0 10px 0;
}

.info {
  margin: 0 0 20px 0;
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
}

table {
  width: 100%;
  border-collapse: collapse;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  overflow: hidden;
}

thead {
  background: rgba(255, 255, 255, 0.1);
}

th, td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  font-size: 14px;
}

th {
  font-weight: 600;
  color: #fff;
  white-space: nowrap;
}

td {
  color: rgba(255, 255, 255, 0.9);
}

.amount {
  font-weight: 600;
  color: #4ade80;
}

.amount-small {
  color: rgba(255, 255, 255, 0.7);
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  color: #000;
  display: inline-block;
}

tbody tr:hover {
  background: rgba(255, 255, 255, 0.05);
}

.empty {
  text-align: center;
  padding: 60px 20px;
  color: rgba(255, 255, 255, 0.5);
}

.empty p {
  font-size: 18px;
}
</style>
