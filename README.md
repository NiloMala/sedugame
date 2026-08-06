# Expedição do Saber

Frontend Next.js da plataforma educacional gamificada. Integra com Laravel Sanctum em modo SPA por cookies — nenhum token é persistido no navegador.

## Rodar localmente

1. Instale as dependências: `npm install`
2. Copie `.env.example` para `.env.local` e configure `NEXT_PUBLIC_API_URL`.
3. Execute `npm run dev`.

Para validar a produção, use `npm run build` e `npm start`.

## Integração

Todas as chamadas passam por `lib/api.ts`, com `credentials: 'include'` e `X-XSRF-TOKEN` em mutações. Os hooks de dados ficam em `lib/hooks.ts`, usando TanStack Query. O contrato em `docs/03-api-contract.md` é a fonte de verdade.

As rotas de atividades do aluno e configurações administrativas não possuem endpoint de leitura no contrato atual; por isso são intencionalmente informativas, sem endpoints inventados.
