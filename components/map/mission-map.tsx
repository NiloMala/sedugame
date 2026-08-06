'use client';

import { useEffect, useRef } from 'react';
import maplibregl from 'maplibre-gl';

const osmStyle: maplibregl.StyleSpecification = { version: 8, sources: { osm: { type: 'raster', tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'], tileSize: 256, attribution: '© OpenStreetMap contributors' } }, layers: [{ id: 'osm', type: 'raster', source: 'osm' }] };
export function MissionMap({ center, onPick }: { center?: [number, number]; onPick?: (latitude: number, longitude: number) => void }) { const element = useRef<HTMLDivElement>(null); useEffect(() => { if (!element.current) return; const map = new maplibregl.Map({ container: element.current, style: osmStyle, center: center ?? [-45.41, -23.62], zoom: 10 }); map.addControl(new maplibregl.NavigationControl(), 'top-right'); let marker: maplibregl.Marker | undefined; map.on('click', (event) => { marker?.remove(); marker = new maplibregl.Marker({ color: '#ffd200' }).setLngLat(event.lngLat).addTo(map); onPick?.(event.lngLat.lat, event.lngLat.lng); }); return () => map.remove(); }, [center, onPick]); return <div ref={element} className="h-full min-h-[300px] w-full" aria-label="Mapa interativo. Clique para marcar uma localização." />; }
