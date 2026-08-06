'use client';

import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { AppShell } from './shell';
import { api, withQuery } from '@/lib/api';
import type { Envelope } from '@/lib/types';

type Row = Record<string, unknown>;
function cell(value: unknown) {
  if (value === null || value === undefined) return '—';
  if (typeof value === 'object') return '—';
  return String(value);
}

export function ResourcePage({ title, description, path, action }: { title: string; description: string; path: string; action?: React.ReactNode }) {
  const [page, setPage] = useState(1);
  const query = useQuery({ queryKey: [path, page], queryFn: () => api<Envelope<Row[]>>(withQuery(path, { page })) });
  const rows = query.data?.data ?? [];
  const columns = Array.from(new Set(rows.flatMap((row) => Object.keys(row).filter((key) => typeof row[key] !== 'object')))).slice(0, 6);
  const meta = query.data?.meta;
  return <AppShell>
    <div className="flex flex-wrap items-end justify-between gap-4"><div><h1 className="text-3xl font-bold">{title}</h1><p className="mt-2 text-slate-600">{description}</p></div>{action}</div>
    <section className="card mt-7 overflow-hidden">
      {query.isLoading ? <p className="p-5">Carregando…</p> : query.error ? <p className="p-5" role="alert">{query.error.message}</p> : rows.length === 0 ? <p className="p-5 text-slate-600">Nenhum registro encontrado.</p> : <div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead className="bg-slate-50 text-slate-700"><tr>{columns.map((column) => <th className="whitespace-nowrap px-4 py-3 font-semibold" key={column}>{column.replaceAll('_', ' ')}</th>)}</tr></thead><tbody>{rows.map((row, index) => <tr className="border-t" key={String(row.id ?? index)}>{columns.map((column) => <td className="whitespace-nowrap px-4 py-3" key={column}>{cell(row[column])}</td>)}</tr>)}</tbody></table></div>}
      {meta && meta.last_page > 1 && <footer className="flex items-center justify-between border-t p-3 text-sm"><span>Página {meta.current_page} de {meta.last_page} · {meta.total} registros</span><div className="flex gap-2"><button className="btn-secondary !px-3 !py-2" disabled={page === 1} onClick={() => setPage((value) => value - 1)} aria-label="Página anterior"><ChevronLeft className="h-4" /></button><button className="btn-secondary !px-3 !py-2" disabled={page === meta.last_page} onClick={() => setPage((value) => value + 1)} aria-label="Próxima página"><ChevronRight className="h-4" /></button></div></footer>}
    </section>
  </AppShell>;
}
