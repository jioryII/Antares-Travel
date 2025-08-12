
<?php

return [
    // Rutas de Autenticación
    'login' => 'AuthController@login',
    'register' => 'AuthController@register',
    'logout' => 'AuthController@logout',
    'forgot-password' => 'AuthController@forgotPassword',

    // Rutas de Usuario
    'user/profile' => 'UserController@profile',
    'user/update' => 'UserController@update',
    'user/delete' => 'UserController@delete',

    // Rutas de Reservas
    'bookings' => 'BookingController@index',
    'bookings/create' => 'BookingController@create',
    'bookings/edit/{id}' => 'BookingController@edit',
    'bookings/delete/{id}' => 'BookingController@delete',

    // Rutas de Destinos
    'destinations' => 'DestinationController@index',
    'destinations/{id}' => 'DestinationController@show',
    'destinations/search' => 'DestinationController@search',

    // Rutas de Admin
    'admin/dashboard' => [
        'controller' => 'AdminController@dashboard',
        'middleware' => ['auth', 'admin']
    ],
    
    // Rutas con métodos HTTP específicos
    'api/users' => [
        'GET' => 'ApiController@getUsers',
        'POST' => 'ApiController@createUser',
        'PUT' => 'ApiController@updateUser',
        'DELETE' => 'ApiController@deleteUser'
    ]
];