'use client';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, signIn, unwrap } from './api';
import type { AnswerResult, Attempt, Campaign, CampaignDetail, Envelope, Mission, Question, User } from './types';
export const useMe = () => useQuery({ queryKey: ['me'], queryFn: () => api<Envelope<User>>('/api/me').then(unwrap), retry: false });
export const useCampaigns = (enabled = true) => useQuery({ queryKey: ['campaigns'], queryFn: () => api<Envelope<Campaign[]>>('/api/campaigns').then(unwrap), enabled });
export const useCampaign = (id: string) => useQuery({ queryKey: ['campaign', id], queryFn: () => api<Envelope<CampaignDetail>>(`/api/campaigns/${id}`).then(unwrap), enabled: !!id });
export const useMission = (id: string) => useQuery({ queryKey: ['mission', id], queryFn: () => api<Envelope<Mission>>(`/api/missions/${id}`).then(unwrap), enabled: !!id });
export const useAttempt = (id?: number) => useQuery({ queryKey: ['attempt', id, 'question'], queryFn: () => api<Envelope<Question>>(`/api/attempts/${id}/next-question`).then(unwrap), enabled: !!id });
export function useLogin() { const client = useQueryClient(); return useMutation({ mutationFn: ({login,password}:{login:string;password:string}) => signIn(login,password), onSuccess: () => client.invalidateQueries({ queryKey: ['me'] }) }); }
export const useStartAttempt = () => useMutation({ mutationFn: (mission_id: number) => api<Envelope<Attempt>>('/api/attempts', { method: 'POST', body: JSON.stringify({ mission_id, activity_id: null }) }).then(unwrap) });
export const useAnswer = (id: number, questionId?: number) => useMutation({ mutationFn: (body: Record<string, unknown>) => api<Envelope<AnswerResult>>(`/api/attempts/${id}/answers`, { method: 'POST', body: JSON.stringify({ question_id: questionId, ...body }) }).then(unwrap) });
export const useHint = (id: number) => useMutation({ mutationFn: (hintId: number) => api<Envelope<{content:string}>>(`/api/attempts/${id}/hints/${hintId}`, { method: 'POST' }).then(unwrap) });
export const useCompleteAttempt = (id: number) => useMutation({ mutationFn: () => api<Envelope<{score:number;experience_gained:number;level_up:boolean;achievements_unlocked:{id:number;title:string;icon:string}[]}>>(`/api/attempts/${id}/complete`, { method: 'POST' }).then(unwrap) });
