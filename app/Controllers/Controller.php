<?php

namespace App\Controllers;

/**
 * General app controller
 *
 * Class Controller
 * @package App\Controllers
 */


class Controller
{
    /**
     * Home page
     */
    public function home(){
        // Since it's just API, just return some data, e.g. version, stability status, etc:
        return [
            'status'    => 'Ok',
            'version'   => '0.1 alpha'
        ];
    }
}