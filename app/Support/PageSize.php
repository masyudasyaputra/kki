<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class PageSize
{
    public static function paginate(EloquentBuilder|QueryBuilder $query, Request $request, int $default = 10): LengthAwarePaginator
    {
        $requested = $request->query('per_page');

        if ($requested === 'all') {
            $total = (clone $query)->count();
            $queryString = $request->query();
            unset($queryString['page']);

            return new LengthAwarePaginator(
                $query->get(),
                $total,
                max($total, 1),
                1,
                ['path' => Paginator::resolveCurrentPath(), 'query' => $queryString]
            );
        }

        $perPage = in_array((string) $requested, ['10', '25', '50', '100'], true)
            ? (int) $requested
            : $default;

        return $query->paginate($perPage)->withQueryString();
    }
}
