export type Role = 'student' | 'teacher' | 'coordinator' | 'director' | 'school_admin' | 'department_admin' | 'super_admin';
export interface Envelope<T> { data: T; meta?: PaginationMeta; links?: PaginationLinks }
export interface PaginationMeta { current_page: number; last_page: number; per_page: number; total: number }
export interface PaginationLinks { first: string; last: string; prev: string | null; next: string | null }
export interface User { id: number; name: string; email: string; role: Role; avatar_url: string | null; school: { id: number; name: string }; student?: { id: number; class: { id: number; name: string }; level: { id: number; name: string; order: number }; experience: number; experience_to_next_level: number } }
export interface Campaign { id: number; title: string; slug: string; cover_image_url: string | null; primary_subject: { id: number; name: string; color: string }; grade: { id: number; name: string }; difficulty: 'easy'|'medium'|'hard'; missions_count: number; estimated_minutes: number; progress: { percent: number; status: 'in_progress'|'completed'|'available' } }
export interface CampaignDetail extends Campaign { missions: { id: number; title: string; order: number; status: string; locked: boolean }[] }
export interface Mission { id: number; campaign_id: number; title: string; narrative: string; difficulty: string; max_score: number; stages: Stage[] }
export interface Stage { id: number; order: number; content: string; location?: { id: number; name: string; latitude: number; longitude: number }; media: { id: number; type: string; file_url: string }[] }
export interface Attempt { id: number; status: string; started_at: string }
export interface QuestionOption { id: number; text: string; pair_side?: 'left' | 'right' }
export interface Question { id: number; statement: string; type: 'single_choice'|'multiple_choice'|'true_false'|'map_location'|'short_answer'|'fill_blank'|'ordering'|'matching'; options?: QuestionOption[]; hints?: { id: number; label?: string }[]; time_limit_seconds?: number; stage?: Stage; blanks_count?: number }
export interface AnswerResult { is_correct: boolean; score: number; distance_meters?: number; explanation: string; correct_option_id?: number }
