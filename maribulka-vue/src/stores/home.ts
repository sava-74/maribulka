import { defineStore } from 'pinia'
import { ref } from 'vue'

const API_URL = '/api'

export const useHomeStore = defineStore('home', () => {
  // Блоки домашней страницы (4 независимых)
  const blocks = ref<Record<number, string>>({})

  async function fetchBlock(id: number): Promise<void> {
    try {
      const response = await fetch(`${API_URL}/home_blocks.php?id=${id}`)
      const data = await response.json()
      if (data.success) {
        blocks.value[id] = data.content
      }
    } catch (err) {
      console.error(`Ошибка загрузки блока ${id}:`, err)
    }
  }

  async function saveBlock(id: number, content: string): Promise<{ success: boolean; message?: string }> {
    try {
      const response = await fetch(`${API_URL}/home_blocks.php`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, content })
      })
      const data = await response.json()
      if (data.success) {
        blocks.value[id] = content
        return { success: true }
      }
      return { success: false, message: data.message }
    } catch (err) {
      return { success: false, message: 'Ошибка сети' }
    }
  }

  return {
    blocks,
    fetchBlock,
    saveBlock
  }
})
