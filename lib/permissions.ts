import type { Role } from './types';

const adminRoles: Role[] = ['school_admin', 'department_admin', 'super_admin'];
const reportRoles: Role[] = ['coordinator', 'director', ...adminRoles];

export function canAccessPath(role: Role, path: string) {
  if (path.startsWith('/teacher')) return role === 'teacher';
  if (path.startsWith('/admin')) return adminRoles.includes(role);
  if (path.startsWith('/reports')) return reportRoles.includes(role);
  if (path.startsWith('/passport') || path.startsWith('/achievements') || path.startsWith('/campaigns') || path.startsWith('/missions') || path.startsWith('/play') || path.startsWith('/activities')) return role === 'student';
  return true;
}

export function homeForRole(role: Role) {
  if (role === 'coordinator' || role === 'director') return '/reports';
  return role === 'student' || role === 'teacher' ? '/dashboard' : '/admin';
}
