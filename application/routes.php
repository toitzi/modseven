<?php

use KO7\Route;

Route::set('default', '(<controller>(/<action>(/<id>)))')
    ->defaults([
        'controller' => 'Welcome',
        'action' => 'index',
    ]);
