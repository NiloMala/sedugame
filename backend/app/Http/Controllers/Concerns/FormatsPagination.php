<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Formata um paginator no shape exato do contrato (docs/03-api-contract.md):
 * { data, meta: {current_page,last_page,per_page,total}, links: {first,last,prev,next} }.
 *
 * Sem isso, `['data' => $query->paginate()]` produz um shape ANINHADO errado
 * (paginator inteiro dentro de "data", com current_page/total soltos por fora
 * de "meta") — foi um bug real no Sprint 1, corrigido retroativamente.
 */
trait FormatsPagination
{
    protected function paginated(LengthAwarePaginator $paginator, ?callable $transform = null): array
    {
        $items = $paginator->items();
        if ($transform) {
            $items = array_map($transform, $items);
        }

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }
}
