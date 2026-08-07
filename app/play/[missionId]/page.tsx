'use client';

import { useEffect, useMemo, useRef, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import {
  ArrowDown,
  ArrowLeft,
  ArrowRight,
  ArrowUp,
  Check,
  ChevronRight,
  Clock3,
  Compass,
  Flag,
  Lightbulb,
  MapPin,
  Sparkles,
  Trophy,
  X,
} from 'lucide-react';
import { MissionMap } from '@/components/map/mission-map-wrapper';
import { CelebrationScreen } from '@/components/celebration-screen';
import { ApiError } from '@/lib/api';
import { useAnswer, useAttempt, useCompleteAttempt, useHint, useMission, useStartAttempt } from '@/lib/hooks';
import type { AnswerResult, Question, QuestionOption } from '@/lib/types';

const optionClass = (selected: boolean) =>
  `flex w-full items-center gap-3 rounded-2xl border-2 px-4 py-3 text-left transition ${
    selected ? 'border-sky-600 bg-sky-50 text-sky-950 shadow-sm' : 'border-slate-200 bg-white hover:border-sky-300 hover:bg-sky-50/40'
  }`;

export default function PlayPage({ params }: { params: { missionId: string } }) {
  const client = useQueryClient();
  const mission = useMission(params.missionId);
  const startAttempt = useStartAttempt();
  const [attemptId, setAttemptId] = useState<number>();
  const question = useAttempt(attemptId);
  const answer = useAnswer(attemptId ?? 0, question.data?.id);
  const complete = useCompleteAttempt(attemptId ?? 0);
  const hint = useHint(attemptId ?? 0);

  const startedMission = useRef(false);
  const finishing = useRef(false);
  const questionStartedAt = useRef(Date.now());
  const [secondsLeft, setSecondsLeft] = useState<number>();
  const [hintsUsed, setHintsUsed] = useState(0);
  const [hintContent, setHintContent] = useState<string>();
  const [confirmHint, setConfirmHint] = useState(false);
  const [result, setResult] = useState<AnswerResult>();
  const [finished, setFinished] = useState<{ score: number; experience_gained: number; level_up: boolean; achievements_unlocked: { id: number; title: string; icon: string }[] }>();

  const [single, setSingle] = useState<number>();
  const [multiple, setMultiple] = useState<number[]>([]);
  const [shortAnswer, setShortAnswer] = useState('');
  const [blanks, setBlanks] = useState<string[]>([]);
  const [ordering, setOrdering] = useState<number[]>([]);
  const [matches, setMatches] = useState<Record<number, number>>({});
  const [mapPoint, setMapPoint] = useState<{ latitude: number; longitude: number }>();

  const q = question.data;
  const options = q?.options ?? [];
  const stageIndex = q?.stage ? Math.max(0, mission.data?.stages.findIndex((stage) => stage.id === q.stage?.id) ?? 0) : 0;
  const stage = q?.stage ?? mission.data?.stages[stageIndex];
  const stageTotal = mission.data?.stages.length ?? 0;
  const hasMedia = stage?.media.find((item) => item.type === 'image' || /\.(png|jpe?g|webp)$/i.test(item.file_url));
  const mapCenter = stage?.location ? [stage.location.longitude, stage.location.latitude] as [number, number] : undefined;
  const leftOptions = options.filter((option) => option.pair_side !== 'right');
  const rightOptions = options.filter((option) => option.pair_side === 'right');

  useEffect(() => {
    if (!mission.data || startedMission.current) return;
    startedMission.current = true;
    startAttempt.mutate(mission.data.id, { onSuccess: (attempt) => setAttemptId(attempt.id) });
  }, [mission.data, startAttempt]);

  useEffect(() => {
    if (!q) return;
    questionStartedAt.current = Date.now();
    setSecondsLeft(q.time_limit_seconds);
    setHintsUsed(0);
    setHintContent(undefined);
    setResult(undefined);
    setSingle(undefined);
    setMultiple([]);
    setShortAnswer('');
    setBlanks(Array.from({ length: q.blanks_count ?? 1 }, () => ''));
    setOrdering(options.map((option) => option.id));
    setMatches({});
    setMapPoint(undefined);
  }, [q?.id]);

  useEffect(() => {
    if (secondsLeft === undefined || secondsLeft <= 0 || result) return;
    const timer = window.setInterval(() => setSecondsLeft((current) => Math.max(0, (current ?? 0) - 1)), 1000);
    return () => window.clearInterval(timer);
  }, [secondsLeft, result]);

  useEffect(() => {
    if (!attemptId || !isNoMoreQuestions(question.error) || finishing.current) return;
    finishing.current = true;
    complete.mutate(undefined, { onSuccess: setFinished });
  }, [attemptId, question.error, complete]);

  const canSubmit = useMemo(() => {
    if (!q || result) return false;
    if (q.type === 'multiple_choice') return multiple.length > 0;
    if (q.type === 'short_answer') return shortAnswer.trim().length > 0;
    if (q.type === 'fill_blank') return blanks.length > 0 && blanks.every((blank) => blank.trim());
    if (q.type === 'ordering') return ordering.length === options.length;
    if (q.type === 'matching') return leftOptions.length > 0 && leftOptions.every((option) => matches[option.id]);
    if (q.type === 'map_location') return !!mapPoint;
    return !!single;
  }, [blanks, leftOptions, mapPoint, matches, multiple, options.length, ordering.length, q, result, shortAnswer, single]);

  function submitAnswer() {
    if (!q || !canSubmit || secondsLeft === 0) return;
    const body: Record<string, unknown> = {
      time_spent_seconds: Math.max(1, Math.round((Date.now() - questionStartedAt.current) / 1000)),
      hints_used: hintsUsed,
    };
    if (q.type === 'multiple_choice') body.selected_option_ids = multiple;
    else if (q.type === 'fill_blank') body.answer_text = blanks;
    else if (q.type === 'ordering') body.ordered_option_ids = ordering;
    else if (q.type === 'matching') body.matches = Object.entries(matches).map(([leftId, rightId]) => ({ left_option_id: Number(leftId), right_option_id: rightId }));
    else if (q.type === 'short_answer') body.answer_text = shortAnswer;
    else if (q.type === 'map_location') Object.assign(body, mapPoint);
    else body.selected_option_id = single;
    answer.mutate(body, { onSuccess: setResult });
  }

  function continueJourney() {
    setResult(undefined);
    client.invalidateQueries({ queryKey: ['attempt', attemptId, 'question'] });
  }

  function moveOption(index: number, direction: -1 | 1) {
    const nextIndex = index + direction;
    if (nextIndex < 0 || nextIndex >= ordering.length) return;
    setOrdering((current) => {
      const next = [...current];
      [next[index], next[nextIndex]] = [next[nextIndex], next[index]];
      return next;
    });
  }

  const formatTime = (value?: number) => `${String(Math.max(0, value ?? 0) / 60 | 0).padStart(2, '0')}:${String(Math.max(0, value ?? 0) % 60).padStart(2, '0')}`;

  if (finished) return <CelebrationScreen result={finished} />;
  if (mission.isLoading || startAttempt.isPending || !attemptId || question.isLoading) return <LoadingScreen />;
  if (mission.isError || startAttempt.isError) return <Failure text="Não foi possível iniciar esta missão. Tente novamente." />;
  if (question.isError) return isNoMoreQuestions(question.error) ? <LoadingScreen text="Preparando sua celebração…" /> : <Failure text={question.error instanceof Error ? question.error.message : 'Não foi possível carregar a próxima questão. Tente novamente.'} />;
  if (!q) return <Failure text="Nenhuma questão foi encontrada para esta missão." />;

  return <main className="min-h-screen bg-[#edf7fb] text-slate-900">
    <header className="border-b border-sky-100 bg-white/95 px-4 py-3 shadow-sm backdrop-blur md:px-8">
      <div className="mx-auto flex max-w-7xl items-center justify-between gap-4">
        <a href="/dashboard" className="inline-flex items-center gap-2 text-sm font-bold text-sky-800 hover:text-sky-600"><ArrowLeft size={18} /> Sair da missão</a>
        <div className="hidden text-center sm:block"><p className="text-xs font-bold uppercase tracking-[.18em] text-sky-600">Expedição do Saber</p><p className="font-bold">{mission.data?.title}</p></div>
        <div className="flex items-center gap-2 rounded-full bg-slate-900 px-3 py-2 text-sm font-bold text-white"><Trophy size={16} className="text-amber-300" /> {stageIndex + 1}/{Math.max(1, stageTotal)}</div>
      </div>
    </header>

    <div className="mx-auto grid max-w-7xl gap-6 p-4 md:p-8 lg:grid-cols-[minmax(0,1.3fr)_minmax(360px,.9fr)]">
      <section className="overflow-hidden rounded-[2rem] border border-sky-100 bg-slate-900 shadow-xl">
        <div className="relative min-h-[360px] md:min-h-[calc(100vh-10rem)]">
          {q.type === 'map_location' ? <MissionMap center={mapCenter} onPick={(latitude, longitude) => setMapPoint({ latitude, longitude })} /> : hasMedia ? <img src={hasMedia.file_url} alt="Ilustração da etapa da missão" className="absolute inset-0 h-full w-full object-cover" /> : stage?.location ? <MissionMap center={mapCenter} interactive={false} /> : <div className="absolute inset-0 grid place-items-center overflow-hidden bg-[radial-gradient(circle_at_70%_20%,#2dd4bf,transparent_27%),radial-gradient(circle_at_20%_80%,#0284c7,transparent_35%),#0f2744]"><Compass className="h-40 w-40 text-white/20" /><div className="absolute bottom-8 left-8 max-w-sm text-white"><p className="text-sm font-bold uppercase tracking-[.2em] text-cyan-200">Ponto de exploração</p><p className="mt-2 text-2xl font-black">{stage?.location?.name ?? 'Caraguatatuba'}</p></div></div>}
          {q.type === 'map_location' && <div className="absolute bottom-5 left-5 rounded-2xl bg-slate-950/85 px-4 py-3 text-sm font-semibold text-white shadow-lg"><MapPin className="mr-2 inline text-amber-300" size={18} />{mapPoint ? 'Local marcado. Confirme sua resposta.' : 'Clique no mapa para marcar sua resposta.'}</div>}
          {stage?.content && <div className="absolute left-5 top-5 max-w-md rounded-2xl bg-white/92 p-4 text-sm font-medium text-slate-700 shadow-lg backdrop-blur">{stage.content}</div>}
        </div>
      </section>

      <aside className="flex flex-col rounded-[2rem] border border-sky-100 bg-white p-5 shadow-xl md:p-7">
        <div className="mb-5 flex items-center justify-between gap-3">
          <div><p className="text-xs font-bold uppercase tracking-[.16em] text-sky-600">Etapa {stageIndex + 1} de {Math.max(1, stageTotal)}</p><div className="mt-2 h-2 w-40 overflow-hidden rounded-full bg-sky-100"><div className="h-full rounded-full bg-gradient-to-r from-sky-600 to-cyan-400" style={{ width: `${((stageIndex + 1) / Math.max(1, stageTotal)) * 100}%` }} /></div></div>
          {q.time_limit_seconds && <div className={`flex items-center gap-2 rounded-xl px-3 py-2 font-black ${secondsLeft && secondsLeft < 15 ? 'bg-rose-100 text-rose-700' : 'bg-amber-50 text-amber-700'}`}><Clock3 size={18} /> {formatTime(secondsLeft)}</div>}
        </div>
        <div className="rounded-2xl bg-sky-50 p-4"><p className="text-sm leading-6 text-slate-700">{mission.data?.narrative}</p></div>
        <h1 className="mt-6 text-xl font-black leading-snug text-slate-900">{q.statement}</h1>
        <QuestionInputs q={q} options={options} single={single} setSingle={setSingle} multiple={multiple} setMultiple={setMultiple} shortAnswer={shortAnswer} setShortAnswer={setShortAnswer} blanks={blanks} setBlanks={setBlanks} ordering={ordering} moveOption={moveOption} leftOptions={leftOptions} rightOptions={rightOptions} matches={matches} setMatches={setMatches} />
        {hintContent && <div className="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><Lightbulb className="mr-2 inline text-amber-500" size={18} />{hintContent}</div>}
        {!result ? <div className="mt-6 flex gap-3"><button type="button" onClick={() => setConfirmHint(true)} disabled={!q.hints?.length || hint.isPending} className="inline-flex items-center gap-2 rounded-xl border border-amber-300 px-4 py-3 text-sm font-bold text-amber-800 disabled:cursor-not-allowed disabled:opacity-50"><Lightbulb size={17} /> Pedir pista</button><button type="button" onClick={submitAnswer} disabled={!canSubmit || answer.isPending || secondsLeft === 0} className="flex flex-1 items-center justify-center gap-2 rounded-xl bg-sky-700 px-4 py-3 font-black text-white shadow-lg shadow-sky-200 transition hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-50">{answer.isPending ? 'Verificando…' : 'Confirmar resposta'} <ArrowRight size={18} /></button></div> : <AnswerFeedback result={result} onContinue={continueJourney} />}
      </aside>
    </div>

    {confirmHint && <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/55 p-4"><div className="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl"><div className="flex items-start justify-between"><div className="rounded-2xl bg-amber-100 p-3 text-amber-600"><Lightbulb /></div><button onClick={() => setConfirmHint(false)} aria-label="Fechar"><X /></button></div><h2 className="mt-4 text-xl font-black">Usar uma pista?</h2><p className="mt-2 text-slate-600">A pista pode ajudar na investigação, mas aplica uma penalidade de pontuação nesta questão.</p><div className="mt-6 flex gap-3"><button onClick={() => setConfirmHint(false)} className="flex-1 rounded-xl border border-slate-200 py-3 font-bold">Voltar</button><button onClick={() => hint.mutate(q.hints![0].id, { onSuccess: (data) => { setHintContent(data.content); setHintsUsed((value) => value + 1); setConfirmHint(false); } })} className="flex-1 rounded-xl bg-amber-400 py-3 font-black text-amber-950">Usar pista</button></div></div></div>}
  </main>;
}

function QuestionInputs({ q, options, single, setSingle, multiple, setMultiple, shortAnswer, setShortAnswer, blanks, setBlanks, ordering, moveOption, leftOptions, rightOptions, matches, setMatches }: { q: Question; options: QuestionOption[]; single?: number; setSingle: (id?: number) => void; multiple: number[]; setMultiple: (ids: number[]) => void; shortAnswer: string; setShortAnswer: (value: string) => void; blanks: string[]; setBlanks: (value: string[]) => void; ordering: number[]; moveOption: (index: number, direction: -1 | 1) => void; leftOptions: QuestionOption[]; rightOptions: QuestionOption[]; matches: Record<number, number>; setMatches: (value: Record<number, number>) => void }) {
  if (q.type === 'short_answer') return <textarea value={shortAnswer} onChange={(event) => setShortAnswer(event.target.value)} placeholder="Escreva sua resposta…" className="mt-5 min-h-28 w-full rounded-2xl border-2 border-slate-200 p-4 outline-none focus:border-sky-500" />;
  if (q.type === 'fill_blank') return <div className="mt-5 space-y-3">{blanks.map((blank, index) => <input key={index} value={blank} onChange={(event) => setBlanks(blanks.map((value, itemIndex) => itemIndex === index ? event.target.value : value))} placeholder={`Resposta da lacuna ${index + 1}`} className="w-full rounded-2xl border-2 border-slate-200 p-3 outline-none focus:border-sky-500" />)}</div>;
  if (q.type === 'ordering') return <div className="mt-5 space-y-2">{ordering.map((id, index) => { const option = options.find((item) => item.id === id); return <div key={id} className="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3"><span className="grid h-7 w-7 place-items-center rounded-full bg-sky-100 text-sm font-black text-sky-800">{index + 1}</span><span className="flex-1 font-medium">{option?.text}</span><button onClick={() => moveOption(index, -1)} disabled={index === 0} aria-label="Mover para cima" className="rounded-lg p-1 hover:bg-slate-100 disabled:opacity-30"><ArrowUp size={18} /></button><button onClick={() => moveOption(index, 1)} disabled={index === ordering.length - 1} aria-label="Mover para baixo" className="rounded-lg p-1 hover:bg-slate-100 disabled:opacity-30"><ArrowDown size={18} /></button></div>; })}</div>;
  if (q.type === 'matching') return <div className="mt-5 space-y-3">{leftOptions.map((left) => <label key={left.id} className="grid grid-cols-[1fr_auto] items-center gap-3 rounded-2xl border border-slate-200 p-3 text-sm font-semibold"><span>{left.text}</span><select value={matches[left.id] ?? ''} onChange={(event) => setMatches({ ...matches, [left.id]: Number(event.target.value) })} className="max-w-44 rounded-lg border border-slate-300 bg-white p-2"><option value="">Associar…</option>{rightOptions.map((right) => <option key={right.id} value={right.id}>{right.text}</option>)}</select></label>)}</div>;
  if (q.type === 'map_location') return null;
  return <div className="mt-5 space-y-3">{options.map((option) => { const isMultiple = q.type === 'multiple_choice'; const selected = isMultiple ? multiple.includes(option.id) : single === option.id; return <button type="button" key={option.id} onClick={() => isMultiple ? setMultiple(selected ? multiple.filter((id) => id !== option.id) : [...multiple, option.id]) : setSingle(option.id)} className={optionClass(selected)}><span className={`grid h-6 w-6 shrink-0 place-items-center border-2 ${isMultiple ? 'rounded-md' : 'rounded-full'} ${selected ? 'border-sky-600 bg-sky-600 text-white' : 'border-slate-300'}`}>{selected && <Check size={15} />}</span>{option.text}</button>; })}</div>;
}

function AnswerFeedback({ result, onContinue }: { result: AnswerResult; onContinue: () => void }) { return <div className={`mt-6 rounded-2xl border p-4 ${result.is_correct ? 'border-emerald-200 bg-emerald-50' : 'border-orange-200 bg-orange-50'}`}><div className="flex gap-3"><div className={`grid h-10 w-10 place-items-center rounded-full ${result.is_correct ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white'}`}>{result.is_correct ? <Check /> : <Compass />}</div><div><p className="font-black">{result.is_correct ? `Muito bem! +${result.score} pontos` : 'Continue explorando!'}</p><p className="mt-1 text-sm leading-5 text-slate-700">{result.explanation}</p>{result.distance_meters !== undefined && <p className="mt-2 text-sm font-bold text-slate-700">Distância: {Math.round(result.distance_meters)} m</p>}</div></div><button onClick={onContinue} className="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 py-3 font-black text-white">Próxima etapa <ChevronRight size={18} /></button></div>; }

function LoadingScreen({ text = 'Preparando sua expedição…' }: { text?: string }) { return <main className="grid min-h-screen place-items-center bg-[#edf7fb]"><div className="text-center"><Compass className="mx-auto h-12 w-12 animate-spin text-sky-600" /><p className="mt-4 font-bold text-slate-700">{text}</p></div></main>; }
function isNoMoreQuestions(error: unknown) { return error instanceof ApiError && error.status === 404 && error.message === 'no_more_questions'; }
function Failure({ text }: { text: string }) { return <main className="grid min-h-screen place-items-center bg-[#edf7fb] p-6"><div className="max-w-md rounded-3xl bg-white p-8 text-center shadow-xl"><Flag className="mx-auto text-rose-500" size={42} /><h1 className="mt-4 text-xl font-black">Ops!</h1><p className="mt-2 text-slate-600">{text}</p><a href="/dashboard" className="mt-6 inline-flex rounded-xl bg-sky-700 px-5 py-3 font-bold text-white">Voltar ao painel</a></div></main>; }
function CompletionScreen({ result }: { result: { score: number; experience_gained: number; level_up: boolean; achievements_unlocked: { id: number; title: string; icon: string }[] } }) { return <main className="relative grid min-h-screen place-items-center overflow-hidden bg-[radial-gradient(circle_at_top,#0ea5e9,transparent_35%),linear-gradient(135deg,#082f49,#0f172a)] p-5 text-white"><Sparkles className="absolute left-[10%] top-[13%] h-16 w-16 animate-pulse text-amber-300" /><Sparkles className="absolute bottom-[12%] right-[12%] h-20 w-20 animate-pulse text-cyan-200" /><section className="relative w-full max-w-xl rounded-[2rem] border border-white/20 bg-white/10 p-8 text-center shadow-2xl backdrop-blur md:p-12"><div className="mx-auto grid h-20 w-20 place-items-center rounded-full bg-amber-300 text-amber-950 shadow-[0_0_45px_#fcd34d]"><Trophy size={42} /></div><p className="mt-6 text-sm font-black uppercase tracking-[.25em] text-cyan-200">Missão concluída</p><h1 className="mt-2 text-4xl font-black">Expedição completa!</h1><div className="mt-8 grid grid-cols-2 gap-3"><div className="rounded-2xl bg-white/10 p-4"><p className="text-3xl font-black text-amber-300">{result.score}</p><p className="text-sm text-slate-200">pontos</p></div><div className="rounded-2xl bg-white/10 p-4"><p className="text-3xl font-black text-cyan-200">+{result.experience_gained}</p><p className="text-sm text-slate-200">XP ganho</p></div></div>{result.level_up && <div className="mt-5 rounded-2xl bg-amber-300 p-4 font-black text-amber-950">🎉 Você subiu de nível!</div>}{result.achievements_unlocked.length > 0 && <div className="mt-6 text-left"><p className="font-black">Conquistas desbloqueadas</p><div className="mt-3 space-y-2">{result.achievements_unlocked.map((achievement) => <div key={achievement.id} className="flex items-center gap-3 rounded-xl bg-white/10 p-3"><span className="text-2xl">{achievement.icon || '🏅'}</span><span className="font-bold">{achievement.title}</span></div>)}</div></div>}<a href="/dashboard" className="mt-8 inline-flex rounded-xl bg-white px-6 py-3 font-black text-sky-900">Voltar ao painel</a></section></main>; }
