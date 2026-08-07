'use client';

import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import * as Icons from 'lucide-react';
import { X } from 'lucide-react';
import { api } from '@/lib/api';
import type { Envelope } from '@/lib/types';

type Accessory = { id: number; name: string; icon: string; image_url?: string | null };
type Avatar = { avatar_base: string; equipped_accessory: Accessory | null; available_bases: { code: string; label: string; color: string }[]; unlocked_accessories: Accessory[] };
const baseColors: Record<string, string> = { compass: '#0EA5E9', map: '#16A34A', binoculars: '#D97706', telescope: '#7C3AED', mountain: '#059669', backpack: '#DC2626' };

export function AvatarIcon({ name, className }: { name?: string; className?: string }) {
  const key = name?.replace(/(^|[-_])([a-z])/g, (_, __, letter) => letter.toUpperCase()) || 'Compass';
  const Icon = (Icons as unknown as Record<string, React.ComponentType<{ className?: string }>>)[key] || Icons.Compass;
  return <Icon className={className} />;
}

export function AvatarVisual({ base, accessory }: { base?: string; accessory?: Accessory | null }) {
  const color = baseColors[base ?? 'compass'] ?? '#0EA5E9';
  return <div className="relative flex w-32 flex-col items-center pb-3"><span className="absolute bottom-0 h-5 w-24 rounded-[50%] bg-slate-950/15 blur-sm" /><span className="relative grid h-28 w-28 place-items-center rounded-full border-4 border-white/80 text-white" style={{ background: `radial-gradient(circle at 32% 25%, white 0, ${color} 9%, ${color} 48%, #082f49 125%)`, boxShadow: `0 16px 38px ${color}55, inset 0 0 28px rgba(255,255,255,.22)` }}><span className="absolute inset-2 rounded-full border border-white/25" /><AvatarIcon name={base} className="relative h-14 w-14 drop-shadow-lg" />{accessory && <span className="absolute -bottom-2 -right-2 grid h-12 w-12 place-items-center rounded-full border-4 border-amber-100 bg-gradient-to-br from-amber-300 to-orange-500 text-amber-950 shadow-[0_0_22px_rgba(251,191,36,.75)]"><AvatarIcon name={accessory.icon} className="h-6 w-6" /></span>}</span><span className="relative mt-2 h-2 w-20 rounded-full bg-gradient-to-r from-transparent via-amber-300 to-transparent" /></div>;
}

export function AvatarEditor({ open, onClose }: { open: boolean; onClose: () => void }) {
  const client = useQueryClient();
  const query = useQuery({ queryKey: ['avatar'], queryFn: () => api<Envelope<Avatar>>('/api/avatar').then((response) => response.data), enabled: open });
  const [base, setBase] = useState('compass'); const [accessory, setAccessory] = useState<number | null>(null); const data = query.data;
  useEffect(() => { if (data) { setBase(data.avatar_base); setAccessory(data.equipped_accessory?.id ?? null); } }, [data]);
  const save = useMutation({ mutationFn: () => api('/api/avatar', { method: 'PUT', body: JSON.stringify({ avatar_base: base, equipped_accessory_id: accessory }) }), onSuccess: () => { client.invalidateQueries({ queryKey: ['avatar'] }); client.invalidateQueries({ queryKey: ['passport'] }); client.invalidateQueries({ queryKey: ['me'] }); onClose(); } });
  if (!open) return null;
  return <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/70 p-4 backdrop-blur-sm"><section role="dialog" aria-modal="true" aria-labelledby="avatar-title" className="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-[2rem] border border-sky-100 bg-white p-6 text-slate-900 shadow-2xl sm:p-8"><div className="flex justify-between"><div><p className="text-xs font-black tracking-[.18em] text-sky-700">SEU PERSONAGEM</p><h2 id="avatar-title" className="mt-1 text-2xl font-black">Monte seu avatar</h2></div><button onClick={onClose} aria-label="Fechar editor" className="grid h-10 w-10 place-items-center rounded-full bg-slate-100 hover:bg-slate-200"><X /></button></div>{!data ? <p className="mt-6">Carregando opções…</p> : <><div className="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">{data.available_bases.map((item) => { const selected = base === item.code; return <button key={item.code} onClick={() => setBase(item.code)} aria-pressed={selected} className={`group relative min-h-32 overflow-hidden rounded-2xl border-2 p-4 text-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg ${selected ? 'border-amber-300 ring-4 ring-amber-200/60' : 'border-white/30'}`} style={{ background: `radial-gradient(circle at 25% 15%, rgba(255,255,255,.4), transparent 34%), linear-gradient(145deg, ${item.color}, #082f49)` }}><span className="absolute -bottom-8 -right-6 h-24 w-24 rounded-full border border-white/15" /><AvatarIcon name={item.code} className="relative mx-auto h-12 w-12 drop-shadow-lg transition group-hover:scale-110" /><span className="relative mt-3 block text-sm font-black">{item.label}</span>{selected && <span className="absolute right-2 top-2 rounded-full bg-amber-300 px-2 py-1 text-[10px] font-black text-amber-950">ATIVO</span>}</button>; })}</div><h3 className="mt-7 font-black">Acessório desbloqueado</h3><div className="mt-3 flex flex-wrap gap-2"><button onClick={() => setAccessory(null)} className={`rounded-xl border px-3 py-2 text-sm font-bold ${accessory === null ? 'border-sky-600 bg-sky-50 text-sky-800' : 'border-slate-200'}`}>Nenhum</button>{data.unlocked_accessories.map((item) => <button key={item.id} onClick={() => setAccessory(item.id)} className={`inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-bold transition ${accessory === item.id ? 'border-amber-400 bg-amber-50 text-amber-900 shadow-[0_0_16px_rgba(251,191,36,.3)]' : 'border-slate-200 hover:border-amber-300'}`}><span className="grid h-7 w-7 place-items-center rounded-full bg-amber-200"><AvatarIcon name={item.icon} className="h-4 w-4" /></span>{item.name}</button>)}</div><button onClick={() => save.mutate()} disabled={save.isPending} className="btn-primary mt-7 w-full !py-3.5">{save.isPending ? 'Salvando…' : 'Confirmar avatar'}</button></>}</section></div>;
}
