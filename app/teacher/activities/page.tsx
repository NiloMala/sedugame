'use client';

import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { AppShell } from '@/components/shell';
import { ResourcePage } from '@/components/resource-page';
import { api } from '@/lib/api';

export default function Page() {
  const [open, setOpen] = useState(false);
  return open ? <ActivityForm close={() => setOpen(false)} /> : <ResourcePage title="Atividades" description="Atividades criadas para suas turmas." path="/api/teacher/activities" action={<button className="btn-primary" onClick={() => setOpen(true)}>Nova atividade</button>} />;
}

function ActivityForm({ close }: { close: () => void }) {
  const client = useQueryClient();
  const [form, setForm] = useState({ campaign_id: '', class_ids: '', starts_at: '', ends_at: '', attempt_limit: '1', ranking_enabled: false });
  const create = useMutation({ mutationFn: () => api('/api/teacher/activities', { method: 'POST', body: JSON.stringify({ campaign_id: Number(form.campaign_id), class_ids: form.class_ids.split(',').map((value) => Number(value.trim())).filter(Boolean), starts_at: form.starts_at || null, ends_at: form.ends_at || null, attempt_limit: Number(form.attempt_limit), ranking_enabled: form.ranking_enabled }) }), onSuccess: () => { client.invalidateQueries({ queryKey: ['/api/teacher/activities'] }); close(); } });
  return <AppShell><div className="flex items-center justify-between"><div><h1 className="text-3xl font-bold">Nova atividade</h1><p className="mt-2 text-slate-600">Atribua uma campanha às turmas selecionadas.</p></div><button className="btn-secondary" onClick={close}>Cancelar</button></div><form className="card mt-7 max-w-xl space-y-5 p-6" onSubmit={(event) => { event.preventDefault(); create.mutate(); }}><label className="block font-semibold">ID da campanha<input required inputMode="numeric" value={form.campaign_id} onChange={(event) => setForm({ ...form, campaign_id: event.target.value })} className="mt-2 w-full rounded-xl border p-3" /></label><label className="block font-semibold">IDs das turmas <span className="font-normal text-slate-500">(separe por vírgula)</span><input required value={form.class_ids} onChange={(event) => setForm({ ...form, class_ids: event.target.value })} className="mt-2 w-full rounded-xl border p-3" /></label><div className="grid gap-4 sm:grid-cols-2"><label className="block font-semibold">Início<input type="datetime-local" value={form.starts_at} onChange={(event) => setForm({ ...form, starts_at: event.target.value })} className="mt-2 w-full rounded-xl border p-3" /></label><label className="block font-semibold">Fim<input type="datetime-local" value={form.ends_at} onChange={(event) => setForm({ ...form, ends_at: event.target.value })} className="mt-2 w-full rounded-xl border p-3" /></label></div><label className="block font-semibold">Limite de tentativas<input required min="1" type="number" value={form.attempt_limit} onChange={(event) => setForm({ ...form, attempt_limit: event.target.value })} className="mt-2 w-full rounded-xl border p-3" /></label><label className="flex items-center gap-3"><input type="checkbox" checked={form.ranking_enabled} onChange={(event) => setForm({ ...form, ranking_enabled: event.target.checked })} /> Habilitar ranking da atividade</label>{create.error && <p className="text-red-700" role="alert">{create.error.message}</p>}<button className="btn-primary" disabled={create.isPending}>{create.isPending ? 'Criando…' : 'Criar atividade'}</button></form></AppShell>;
}
