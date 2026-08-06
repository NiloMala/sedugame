import type { Config } from 'tailwindcss';
export default { content: ['./app/**/*.{ts,tsx}', './components/**/*.{ts,tsx}'], theme: { extend: { colors: { brand: 'rgb(var(--brand) / <alpha-value>)', ink: 'rgb(var(--ink) / <alpha-value>)', sand: 'rgb(var(--sand) / <alpha-value>)' } } }, plugins: [] } satisfies Config;
