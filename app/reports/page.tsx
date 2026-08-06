'use client';

import { useQuery } from '@tanstack/react-query';
import { AppShell } from '@/components/shell';
import { api } from '@/lib/api';
import { useMe } from '@/lib/hooks';
import type { Envelope } from '@/lib/types';

export default function ReportsPage() { const { data: me } = useMe(); const network = me?.role === 'department_admin' || me?.role === 'super_admin'; const path = network ? '/api/reports/network' : me?.school ? `/api/reports/school/${me.school.id}` : ''; const report = useQuery({ queryKey: ['reports', path], queryFn: () => api<Envelope<Record<string, unknown>>>(path).then((response) => response.data), enabled: Boolean(path) }); return <AppShell><h1 className="text-3xl font-bold">{network ? 'Painel da Secretaria' : 'Painel da Escola'}</h1><p className="mt-2 text-slate-600">Indicadores educacionais do período selecionado.</p><section className="card mt-7 overflow-hidden"><div className="border-b bg-sky-50 p-4 font-bold">Indicadores</div>{report.isLoading ? <p className="p-5">Carregando…</p> : report.error ? <p className="p-5" role="alert">{report.error.message}</p> : <dl className="grid gap-px bg-slate-100 sm:grid-cols-2">{Object.entries(report.data ?? {}).map(([key, value]) => <div className="bg-white p-5" key={key}><dt className="text-sm text-slate-500">{key.replaceAll('_', ' ')}</dt><dd className="mt-1 text-xl font-bold">{typeof value === 'object' ? 'Ver detalhe na API' : String(value)}</dd></div>)}</dl>}</section></AppShell>; }
