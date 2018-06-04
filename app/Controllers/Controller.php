<?php

namespace App\Controllers;

/**
 * General app controller
 *
 * Class Controller
 * @package App\Controllers
 */
use App\Models\ProductModel;

class Controller
{
    /**
     * Home page
     */
    public function home(ProductModel $model){
        // Since it's just API, just return some data, e.g. version, stability status, etc:
        return [
            'status'    => 'Ok',
            'version'   => '0.1 alpha'
        ];
    }
}