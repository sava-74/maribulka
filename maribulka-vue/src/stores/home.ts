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
  const description = ref<string>('')
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Загрузить фото студии
  async function fetchPhotos() {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/studio_photos.php`)
      const data = await response.json()

      if (data.success) {
        // Создаём массив из 4 элементов, заполняем по позициям
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
        // Обновляем локальный массив
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
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ position })
      })

      const data = await response.json()

      if (data.success) {
        // Обновляем локальный массив
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

  // Загрузить описание студии
  async function fetchDescription() {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/studio_description.php`)
      const data = await response.json()

      if (data.success) {
        description.value = data.data.content || ''
      } else {
        error.value = data.message || 'Ошибка загрузки описания'
      }
    } catch (err) {
      error.value = 'Ошибка сети'
      console.error('Ошибка загрузки описания:', err)
    } finally {
      loading.value = false
    }
  }

  // Обновить описание студии
  async function updateDescription(content: string): Promise<{ success: boolean; message?: string }> {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/studio_description.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ content })
      })

      const data = await response.json()

      if (data.success) {
        description.value = content
        return { success: true }
      } else {
        error.value = data.message || 'Ошибка обновления описания'
        return { success: false, message: data.message }
      }
    } catch (err) {
      error.value = 'Ошибка сети'
      console.error('Ошибка обновления описания:', err)
      return { success: false, message: 'Ошибка сети' }
    } finally {
      loading.value = false
    }
  }

  return {
    photos,
    description,
    loading,
    error,
    fetchPhotos,
    uploadPhoto,
    deletePhoto,
    fetchDescription,
    updateDescription
  }
})
