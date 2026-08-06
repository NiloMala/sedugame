import type { Envelope } from './types';

const API_URL = process.env.NEXT_PUBLIC_API_URL?.replace(/\/$/, '');

export class ApiError extends Error {
  constructor(message: string, public status: number, public fields?: Record<string, string[]>) {
    super(message);
  }
}

function csrfToken() {
  if (typeof document === 'undefined') return '';
  const token = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='))?.split('=')[1];
  return token ? decodeURIComponent(token) : '';
}

export async function api<T>(path: string, init: RequestInit = {}): Promise<T> {
  if (!API_URL) throw new ApiError('NEXT_PUBLIC_API_URL não foi configurada.', 0);
  const method = init.method?.toUpperCase() ?? 'GET';
  const headers = new Headers({ Accept: 'application/json', ...init.headers });
  if (init.body) headers.set('Content-Type', 'application/json');
  if (method !== 'GET') headers.set('X-XSRF-TOKEN', csrfToken());

  const response = await fetch(`${API_URL}${path}`, { ...init, headers, credentials: 'include' });
  if (!response.ok) {
    const error = await response.json().catch(() => ({}));
    throw new ApiError(error.message ?? 'Não foi possível concluir a solicitação.', response.status, error.errors);
  }
  return response.status === 204 ? undefined as T : response.json();
}

export const unwrap = <T>(result: Envelope<T>) => result.data;

export async function signIn(login: string, password: string) {
  if (!API_URL) throw new ApiError('NEXT_PUBLIC_API_URL não foi configurada.', 0);
  const csrf = await fetch(`${API_URL}/sanctum/csrf-cookie`, { credentials: 'include' });
  if (!csrf.ok) throw new ApiError('Não foi possível iniciar a sessão segura.', csrf.status);
  await api<void>('/api/login', { method: 'POST', body: JSON.stringify({ login, password }) });
}

export function withQuery(path: string, params: Record<string, string | number | undefined>) {
  const search = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== '') search.set(key, String(value));
  });
  return search.size ? `${path}?${search}` : path;
}
