<?php

return [
    'home' => [
        'handler' => 'App\Controllers\Controller@home',
        'path' => '/'
    ],
    'index' => [
        'handler' => 'App\Controllers\ProductController@index',
        'path' => '/products',
        'method' => 'GET'
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
        'acl' => ['admin', 'user']
    ],
    'product_update' => [
        'handler' => 'App\Controllers\ProductController@update',
        'path' => '/product/{id}',
        'method' => 'PUT',
        'acl' => ['admin', 'user']
    ],
    'product_delete' => [
        'handler' => 'App\Controllers\ProductController@delete',
        'path' => '/product/{id}',
        'method' => 'DELETE',
        'acl' => ['admin', 'user']
    ],
    'categories' => [
        'handler' => 'App\Controllers\CategoryController@index',
        'path' => '/categories',
        'method' => 'GET',
    ],
    'login' => [
        'handler' => 'Mindk\Framework\Controllers\UserController@login',
        'path' => '/login',
        'method' => 'POST',
    ],
    'register' => [
        'handler' => 'Mindk\Framework\Controllers\UserController@register',
        'path' => '/register',
        'method' => 'POST',
    ],
    'logout' => [
        'handler' => 'Mindk\Framework\Controllers\UserController@logout',
        'path' => '/logout',
        'method' => 'POST',
    ],
];