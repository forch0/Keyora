import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/api/client'
import { setReauthRetry } from '@/features/auth/components/ReauthDialog'
import type { ApiError } from '@/types/api-error'
import type {
  SecureLinkResourceType,
  SecureLink,
  SecureLinkAccess,
  CreateSecureLinkInput,
  PublicLinkInfo,
  PublicLinkResource,
} from '@/types/secure-link'

// ─── Authenticated hooks (resource owner) ───────────────────────────────────

export function useSecureLinks(resource: SecureLinkResourceType, id: number) {
  return useQuery<SecureLink[], ApiError>({
    queryKey: ['secure-links', resource, id],
    queryFn: async () => {
      const res = await api.get<{ data: SecureLink[] }>(`/api/v1/${resource}/${id}/share-links`)
      return res.data
    },
    enabled: id > 0,
  })
}

export function useCreateSecureLink(resource: SecureLinkResourceType, id: number) {
  const queryClient = useQueryClient()
  const mutation = useMutation<{ data: SecureLink }, ApiError, CreateSecureLinkInput>({
    mutationFn: (data) =>
      api.post<{ data: SecureLink }>(`/api/v1/${resource}/${id}/share-links`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['secure-links', resource, id] })
    },
  })

  return {
    ...mutation,
    mutate: (data: CreateSecureLinkInput, options?: Parameters<typeof mutation.mutate>[1]) => {
      const retry = () => mutation.mutate(data, options)
      mutation.mutate(data, {
        ...options,
        onError: (error, ...rest) => {
          if (error.status === 423) {
            setReauthRetry(retry)
          }
          // eslint-disable-next-line @typescript-eslint/no-explicit-any
          const origOnError = (options as any)?.onError
          if (typeof origOnError === 'function') {
            origOnError(error, ...rest)
          }
        },
      })
    },
  }
}

export function useRevokeSecureLink() {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (linkId) => api.delete(`/api/v1/secure-links/${linkId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['secure-links'] })
    },
  })
}

export function useSecureLinkActivity(linkId: number) {
  return useQuery<SecureLinkAccess[], ApiError>({
    queryKey: ['secure-link-activity', linkId],
    queryFn: async () => {
      const res = await api.get<{ data: SecureLinkAccess[] }>(
        `/api/v1/secure-links/${linkId}/activity`,
      )
      return res.data
    },
    enabled: linkId > 0,
  })
}

// ─── Public hooks (no auth required) ────────────────────────────────────────

export function usePublicLinkInfo(uuid: string) {
  return useQuery<PublicLinkInfo, ApiError>({
    queryKey: ['public-link', uuid],
    queryFn: async () => {
      const res = await api.get<{ data: PublicLinkInfo }>(`/api/v1/s/${uuid}`)
      return res.data
    },
    enabled: !!uuid,
  })
}

export function useVerifyPublicLink(uuid: string) {
  return useMutation<{ data: { access_token: string } }, ApiError, { password?: string | null; otp_code?: string | null }>({
    mutationFn: (data) =>
      api.post<{ data: { access_token: string } }>(`/api/v1/s/${uuid}/verify`, data),
  })
}

export function useSendEmailVerification(uuid: string) {
  return useMutation<{ data: { message: string } }, ApiError, { email: string }>({
    mutationFn: (data) =>
      api.post<{ data: { message: string } }>(`/api/v1/s/${uuid}/email-verify`, data),
  })
}

export function useConfirmEmailVerification(uuid: string) {
  return useMutation<{ data: { access_token: string } }, ApiError, { code: string }>({
    mutationFn: (data) =>
      api.post<{ data: { access_token: string } }>(`/api/v1/s/${uuid}/email-confirm`, data),
  })
}

export function usePublicLinkResource(uuid: string, accessToken: string | null) {
  return useQuery<PublicLinkResource, ApiError>({
    queryKey: ['public-link-resource', uuid, accessToken],
    queryFn: async () => {
      const res = await api.get<{ data: PublicLinkResource }>(`/api/v1/s/${uuid}/resource`)
      return res.data
    },
    enabled: !!uuid && !!accessToken,
  })
}
