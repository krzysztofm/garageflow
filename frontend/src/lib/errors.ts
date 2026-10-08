import { isAxiosError } from 'axios'

interface ApiError {
  errors?: Record<string, string[]>
}

export function getValidationErrors(error: unknown): Record<string, string[]> {
  if (isAxiosError<ApiError>(error) && error.response?.status === 422) {
    return error.response.data.errors ?? {}
  }

  return {}
}

export function getErrorMessage(error: unknown): string {
  if (isAxiosError(error)) {
    switch (error.response?.status) {
      case 401:
      case 419:
        return 'Your session has expired. Please log in again.'
      case 403:
        return 'You do not have permission to perform this action.'
      case 409:
        return 'The data has changed. Please refresh the page and try again.'
      case 422:
        return 'Please correct the highlighted fields.'
      case 429:
        return 'Too many requests. Please wait a moment and try again.'
    }

    if (!error.response) {
      return 'Unable to connect to the server.'
    }
  }

  return 'Something went wrong. Please try again.'
}
