'use client';

import Link from 'next/link';
import { useQuery } from '@tanstack/react-query';
import { ArrowRight, BookOpen, Flame, Trophy, Users } from 'lucide-react';
import { AppShell } from '@/components/shell';
import { CampaignCard, XpBar } from '@/components/gamification';
import { api } from '@/lib/api';
import { useCampaigns, useMe } from '@/lib/hooks';
import type { Envelope } from '@/lib/types';

export default function Dashboard() {
  const { data: me } = useMe(); const isStudent = me?.role === 'student'; const isTeacher = me?.role === 'teacher';
  return <AppShell><div className="flex flex-wrap items-end justify-between gap-3"><div><p className="font-bold text-brand">{isTeacher ? 'PAINEL DO PROFESSOR' : isStudent ? 'SUA JORNADA' : 'GESTÃO EDUCACIONAL'}</p><h1 className="mt-1 text-3xl font-bold">Olá, {me?.name?.split(' ')[0] ?? 'explorador'}!</h1><p className="mt-2 text-slate-600">{isTeacher ? 'Acompanhe suas turmas e atividades.' : isStudent ? 'Pronto para a próxima descoberta?' : 'Acesse os recursos de gestão disponíveis para seu perfil.'}</p></div>{isStudent && <Link href="/campaigns" className="btn-primary">Explorar campanhas <ArrowRight className="h-4" /></Link>}</div>{isTeacher ? <TeacherDashboard /> : isStudent ? <StudentDashboard /> : <ManagementDashboard />}</AppShell>;
}

function StudentDashboard() {
  const { data: me } = useMe(); const campaigns = useCampaigns(true); const s = me?.student;
  return <><div className="mt-7 grid gap-5 lg:grid-cols-[1fr_2fr]"><div><XpBar value={s?.experience} next={s?.experience_to_next_level} level={s?.level.name} /><span className="mt-3 inline-flex items-center gap-1 rounded-full bg-orange-100 px-3 py-1 text-sm font-black text-orange-700"><Flame className="h-4" />{s?.streak_days ?? 0} dias seguidos</span></div><section className="card bg-gradient-to-br from-amber-100 to-orange-50 p-5"><p className="font-bold">Sua próxima missão</p><p className="mt-1 text-slate-600">Continue explorando e desbloqueie novas descobertas.</p><Link href="/campaigns" className="mt-4 inline-flex font-bold text-brand">Ver missões <ArrowRight className="ml-1 h-5" /></Link></section></div><section className="mt-9"><div className="mb-4 flex justify-between"><h2 className="text-xl font-bold">Continue explorando</h2><Link href="/campaigns" className="text-sm font-bold text-brand">Ver todas</Link></div>{campaigns.isLoading ? <p>Carregando campanhas…</p> : campaigns.error ? <p role="alert">{campaigns.error.message}</p> : <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">{campaigns.data?.slice(0, 3).map((campaign) => <CampaignCard key={campaign.id} campaign={campaign} />)}</div>}</section></>;
}

function TeacherDashboard() { const classes = useQuery({ queryKey: ['teacher', 'classes'], queryFn: () => api<Envelope<unknown[]>>('/api/teacher/classes').then((response) => response.data) }); const activities = useQuery({ queryKey: ['teacher', 'activities'], queryFn: () => api<Envelope<unknown[]>>('/api/teacher/activities').then((response) => response.data) }); return <div className="mt-8 grid gap-5 md:grid-cols-3"><Metric title="Turmas" value={classes.isLoading ? '…' : String(classes.data?.length ?? 0)} icon={<Users />} /><Metric title="Atividades" value={activities.isLoading ? '…' : String(activities.data?.length ?? 0)} icon={<BookOpen />} /><Metric title="Acompanhar resultados" value="→" icon={<Trophy />} /><section className="card p-5 md:col-span-3"><h2 className="text-xl font-bold">Planeje e acompanhe</h2><p className="mt-2 text-slate-600">Crie atividades e consulte o desempenho das suas turmas.</p><div className="mt-5 flex gap-3"><Link href="/teacher/classes" className="btn-secondary">Ver turmas</Link><Link href="/teacher/activities" className="btn-primary">Gerenciar atividades</Link></div></section></div>; }
function ManagementDashboard() { const { data: me } = useMe(); const reportsOnly = me?.role === 'coordinator' || me?.role === 'director'; return <section className="card mt-8 p-6"><h2 className="text-xl font-bold">{reportsOnly ? 'Indicadores da escola' : 'Recursos de gestão'}</h2><p className="mt-2 text-slate-600">As permissões do seu perfil definem os recursos que podem ser acessados.</p><Link href={reportsOnly ? '/reports' : '/admin'} className="btn-primary mt-5">{reportsOnly ? 'Abrir painel' : 'Abrir gestão'}</Link></section>; }
function Metric({ title, value, icon }: { title: string; value: string; icon: React.ReactNode }) { return <article className="card p-5"><div className="flex items-center justify-between text-brand">{icon}<strong className="text-3xl text-ink">{value}</strong></div><p className="mt-4 text-sm text-slate-600">{title}</p></article>; }
