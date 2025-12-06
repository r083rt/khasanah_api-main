<?php

namespace App\Http\Controllers;

use App\Traits\ResponseTransform;
use App\Traits\PerPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use ResponseTransform;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function boot()
    {
        dd('a');
    }

    /**
     * Per page
     *
     * @param $query
     * @return int
     */
    public function perPage($query = null)
    {
        if ($query instanceof Builder || $query instanceof Collection) {
            $total = $query->count();
        } else {
            $total = 15;
        }

        $perPage = request()->get('per_page');
        $perPage = $perPage < 0 ? 15 : $perPage;

        return (request()->get('page') == 'all') ? $total : (request()->filled('per_page') ? $perPage : 15);
    }
}
