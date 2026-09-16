import { api, type ApiEnvelope } from './api'

interface PresignResponse {
  upload_url: string
  method: 'PUT' | 'POST'
  path: string
}

/**
 * Mirrors the backend's presign -> raw upload -> attach flow (see
 * PropertyMediaController). On this deployment (local/public disk, no S3)
 * the "presigned" URL is actually our own direct-upload route, so the
 * same authenticated `api` client works for every step.
 */
async function uploadFile(propertyId: number, type: 'image' | 'video', file: File, isPrimary: boolean) {
  const extension = file.name.split('.').pop()?.toLowerCase() ?? ''

  const presign = (
    await api.get<ApiEnvelope<PresignResponse>>(`/properties/${propertyId}/${type}/presigned-url`, {
      params: { extension, content_type: file.type },
    })
  ).data.data

  await api.request({
    url: presign.upload_url,
    method: presign.method,
    data: file,
    headers: { 'Content-Type': file.type },
  })

  return api.post(`/properties/${propertyId}/${type}`, {
    path: presign.path,
    is_primary: isPrimary,
  })
}

export function uploadPropertyImage(propertyId: number, file: File, isPrimary = false) {
  return uploadFile(propertyId, 'image', file, isPrimary)
}

export function uploadPropertyVideo(propertyId: number, file: File, isPrimary = false) {
  return uploadFile(propertyId, 'video', file, isPrimary)
}

export function attachYoutubeVideo(propertyId: number, youtubeUrl: string, isPrimary = false) {
  return api.post(`/properties/${propertyId}/videos/youtube`, {
    youtube_url: youtubeUrl,
    is_primary: isPrimary,
  })
}
