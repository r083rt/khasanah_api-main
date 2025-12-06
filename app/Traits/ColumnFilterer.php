<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

trait ColumnFilterer
{
    /**
     * Scope a query to only include based on a given columns.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $columns
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, Request $request, $columns = false, $relation = false)
    {
        $keyword = $request->get('search');

        if (!$columns) {
            $columns = $this->fillable;
        }

        $query->filterByKeyword($keyword, $columns, $relation);

        return $query;
    }

    /**
     * Scope a query to only include based on a given columns.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $columns
     * @param string $keyword
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByKeyword($query, $keyword, $columns, $relation)
    {
        if ($keyword) {
            $query = $query->where(function ($query) use ($keyword, $columns, $relation) {
                $query->where(Arr::first($columns), 'like', '%' . $keyword . '%');
                for ($i = 1; $i < count($columns); $i++) {
                    $query->orWhere($columns[$i], 'like', '%' . $keyword . '%');
                }

                if ($relation) {
                    foreach ($relation as $value) {
                        $query->orWhereHas($value, function ($query) use ($keyword) {
                            $columns = $query->getModel()->getfillable();
                            $query->where(Arr::first($columns), 'like', '%' . $keyword . '%');
                            for ($i = 1; $i < count($columns); $i++) {
                                $query->orWhere($columns[$i], 'like', '%' . $keyword . '%');
                            }
                        });
                    }
                }
            });
        }

        return $query;
    }
}
