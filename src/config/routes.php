<?php

return [
    'index' => 'index.php',
    // Autenticación
    'login'                  => 'src/auth/login.php',
    'register'               => 'src/auth/register.php',
    'logout'                 => 'src/auth/logout.php',
    'forgot-password'        => 'src/auth/forgot_password.php',
    'verificar-email'        => 'src/auth/verificar_email.php',
    'reenviar-verificacion'  => 'src/auth/reenviar_verificacion.php',
    'oauth/callback'         => 'src/auth/oauth_callback.php',

    // Usuario
    'user/profile'           => 'src/auth/profile.php',
    'user/update'            => 'src/auth/update.php',
    'user/delete'            => 'src/auth/delete.php',

    // Reservas
    'bookings'               => 'src/bookings/index.php',
    'bookings/create'        => 'src/bookings/create.php',
    'bookings/edit/{id}'     => 'src/bookings/edit.php',
    'bookings/delete/{id}'   => 'src/bookings/delete.php',

    // Destinos
    'destinations'           => 'src/destinations/index.php',
    'destinations/{id}'      => 'src/destinations/show.php',
    'destinations/search'    => 'src/destinations/search.php',

    // Admin
    'admin/dashboard'        => [
        'file'      => 'src/admin/dashboard.php',
        'middleware'=> ['auth', 'admin']
    ],

    // Documentación
    'docs'                   => 'src/docs/documentacion.md',
    'docs/pendientes'        => 'src/docs/pendientes.md',
    'docs/registro'          => 'src/docs/registro.md',
];