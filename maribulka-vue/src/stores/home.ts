import { defineStore } from 'pinia'
import { ref } from 'vue'

const API_URL = '/api'

interface StudioPhoto {
  id: number
  photo_url: string
  position: number
  created_at: string
}

export const useHomeStore = defineStore('home', () => {
  const photos = ref<string[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Блоки домашней страницы (4 независимых)
  const blocks = ref<Record<number, string>>({})

  // Загрузить фото студии
  async function fetchPhotos() {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/studio_photos.php`)
      const data = await response.json()

      if (data.success) {
        const photoArray: string[] = ['', '', '', '']
        data.photos.forEach((photo: StudioPhoto) => {
          if (photo.position >= 0 && photo.position < 4) {
            photoArray[photo.position] = photo.photo_url
          }
        })
        photos.value = photoArray
      } else {
        error.value = data.message || 'Ошибка загрузки фото'
      }
    } catch (err) {
      error.value = 'Ошибка сети'
      console.error('Ошибка загрузки фото:', err)
    } finally {
      loading.value = false
    }
  }

  // Загрузить новое фото
  async function uploadPhoto(file: File, position: number): Promise<{ success: boolean; message?: string }> {
    loading.value = true
    error.value = null

    try {
      const formData = new FormData()
      formData.append('photo', file)
      formData.append('position', position.toString())

      const response = await fetch(`${API_URL}/studio_photos.php`, {
        method: 'POST',
        body: formData
      })

      const data = await response.json()

      if (data.success) {
        await fetchPhotos()
        return { success: true }
      } else {
        error.value = data.message || 'Ошибка загрузки фото'
        return { success: false, message: data.message }
      }
    } catch (err) {
      error.value = 'Ошибка сети'
      console.error('Ошибка загрузки фото:', err)
      return { success: false, message: 'Ошибка сети' }
    } finally {
      loading.value = false
    }
  }

  // Удалить фото
  async function deletePhoto(position: number): Promise<{ success: boolean; message?: string }> {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/studio_photos.php`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ position })
      })

      const data = await response.json()

      if (data.success) {
        await fetchPhotos()
        return { success: true }
      } else {
        error.value = data.message || 'Ошибка удаления фото'
        return { success: false, message: data.message }
      }
    } catch (err) {
      error.value = 'Ошибка сети'
      console.error('Ошибка удаления фото:', err)
      return { success: false, message: 'Ошибка сети' }
    } finally {
      loading.value = false
    }
  }

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
    photos,
    loading,
    error,
    blocks,
    fetchPhotos,
    uploadPhoto,
    deletePhoto,
    fetchBlock,
    saveBlock
  }
})
