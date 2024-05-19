<?php

return [
    'route' => [
        'prefix' => null,
        'admin_prefix' => 'admin', // required
    ],

    'database' => [
        'autoload_migrations' => true,
    ],

    'default_lang' => 'en',


    /*
    |--------------------------------------------------------------------------
    | Detect RTL language
    |--------------------------------------------------------------------------
    |
    | Below are all RTL languages pre defined, and the website direction will 
    | be changed accordingly
    |
    |
    */
    'rtl_langs' => [
        'ar', // arabic
        'fa', // persian
        'he', // hebrew
        'ms', // malay
        'ur', // urdu
        'ml' // malayalam
    ],



];


