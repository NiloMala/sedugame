'use client';

import Link from 'next/link';
import { BookOpen, Compass, Gem, LogOut, Map, Shield, Trophy, Users } from 'lucide-react';
import { usePathname, useRouter } from 'next/navigation';
import { useEffect } from 'react';
import { api } from '@/lib/api';
import { useMe } from '@/lib/hooks';
import { canAccessPath, homeForRole } from '@/lib/permissions';
import { AccessibilityControls } from './accessibility';

const studentLinks = [
  { href: '/dashboard', label: 'Início', icon: Compass }, { href: '/campaigns', label: 'Campanhas', icon: Map },
  { href: '/activities', label: 'Atividades', icon: BookOpen }, { href: '/passport', label: 'Passaporte', icon: Users }, { href: '/achievements', label: 'Conquistas', icon: Trophy }, { href: '/collections', label: 'Coleção', icon: Gem },
];
const teacherLinks = [
  { href: '/dashboard', label: 'Início', icon: Compass }, { href: '/teacher/classes', label: 'Turmas', icon: Users },
  { href: '/teacher/activities', label: 'Atividades', icon: BookOpen }, { href: '/teacher/reports', label: 'Relatórios', icon: Trophy },
];

export function AppShell({ children }: { children: React.ReactNode }) {
  const { data: me, isError, isLoading } = useMe();
  const router = useRouter();
  const pathname = usePathname();

  useEffect(() => {
    if (!isLoading && isError) router.replace('/login');
    else if (me && !canAccessPath(me.role, pathname)) router.replace(homeForRole(me.role));
  }, [isError, isLoading, me, pathname, router]);

  const links = me?.role === 'teacher' ? teacherLinks : me?.role && me.role !== 'student' ? [{ href: '/admin', label: 'Gestão', icon: Shield }] : studentLinks;
  async function logout() { await api<void>('/api/logout', { method: 'POST' }).catch(() => undefined); router.replace('/login'); }

  if (isLoading) return <main className="grid min-h-screen place-items-center">Carregando…</main>;
  return <div className="min-h-screen lg:grid lg:grid-cols-[244px_1fr]">
    <aside className="border-b bg-ink px-4 py-5 text-white lg:border-b-0 lg:border-r">
      <Link href={me ? homeForRole(me.role) : '/login'} className="flex items-center gap-3 px-3 text-lg font-bold"><span className="grid h-10 w-10 place-items-center rounded-xl bg-amber-400 text-ink"><Compass /></span><span>Expedição<br />do Saber</span></Link>
      <nav className="mt-8 flex gap-1 overflow-auto lg:flex-col">{links.map(({ href, label, icon: Icon }) => <Link key={href} href={href} className="flex shrink-0 items-center gap-3 rounded-xl px-3 py-3 text-slate-200 hover:bg-white/10"><Icon className="h-5 w-5" />{label}</Link>)}</nav>
      <button onClick={logout} className="mt-8 flex items-center gap-3 rounded-xl px-3 py-3 text-slate-200 hover:bg-white/10"><LogOut className="h-5 w-5" />Sair</button>
    </aside>
    <main><header className="flex min-h-16 items-center justify-between border-b bg-white px-5"><p className="font-semibold">{me?.school?.name ?? 'Expedição do Saber'}</p><div className="flex items-center gap-4"><AccessibilityControls /><span className="hidden text-sm sm:block">{me?.name}</span><div className="grid h-9 w-9 place-items-center rounded-full bg-amber-200 font-bold text-ink">{me?.name?.[0] ?? '?'}</div></div></header><div className="mx-auto max-w-7xl p-5 lg:p-8">{children}</div></main>
  </div>;
}
