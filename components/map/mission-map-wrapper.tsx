'use client';
import dynamic from 'next/dynamic';
export const MissionMap = dynamic(()=>import('./mission-map').then(m=>m.MissionMap),{ssr:false,loading:()=> <div className="grid h-full min-h-[300px] place-items-center bg-slate-200">Carregando mapa…</div>});
