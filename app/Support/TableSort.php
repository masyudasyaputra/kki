<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TableSort
{
    /** Only caller-provided columns and relations may be used for ordering. */
    public static function apply(Builder $query, Request $request, array $columns): Builder
    {
        $key = $request->query('sort');
        if (!is_string($key) || !array_key_exists($key, $columns)) {
            return $query->latest();
        }

        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $column = $columns[$key];
        if (is_array($column)) {
            [$relation, $attribute] = $column;
            $query->withAggregate($relation . ' as table_sort_value', $attribute);
            $column = 'table_sort_value';
        }

        return $query->orderBy($column, $direction)->orderBy($query->getModel()->qualifyColumn('id'));
    }
}
