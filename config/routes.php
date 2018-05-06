<?php

return [
    'home' => [
        'handler' => 'App\Controllers\Controller@home',
        'path' => '/'
    ],
    'index' => [
        'handler' => 'App\Controllers\ProductController@index',
        'path' => '/products'
    ],
    'product_show' => [
        'handler' => 'App\Controllers\ProductController@show',
        'path' => '/product/{id}',
        'method' => 'GET'
    ],
    'product_create' => [
        'handler' => 'App\Controllers\ProductController@create',
        'path' => '/product',
        'method' => 'POST',
        'acl' => ['user', 'admin']
    ],
    'login' => [
        'handler' => 'Mindk\Framework\Controllers\UserController@login',
        'path' => '/login',
        'method' => 'POST',
    ]
];