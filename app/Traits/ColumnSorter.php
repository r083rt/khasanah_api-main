<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

trait ColumnSorter
{
    /**
     * Scope a query to only include based on a given columns.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sortBy
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSort($query, Request $request)
    {
        $sortBy = $request->get('sort');
        $sortDirection = $request->get('sort_type');
        $query->sortByColumn($sortBy, $sortDirection);

        return $query;
    }

    /**
     * Scope a query to sort based on a given columns and direction.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSortByColumn($query, $sortBy, $sortDirection = 'asc')
    {
        if ($sortBy) {
            $connection = $this->connection ? $this->connection : config('database.default');
            // if (Schema::connection($connection)->hasColumn($this->getTable(), $sortBy)) {
                $query->orderBy($sortBy, $sortDirection);
            // }
        } else {
            $query->latest();
        }

        return $query;
    }
}
