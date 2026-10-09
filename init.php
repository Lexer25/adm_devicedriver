<?php defined('SYSPATH') OR die('No direct access allowed.');

defined('DEVICEDRIVER_VERSION') OR define('DEVICEDRIVER_VERSION', '1.0.1');

Kohana::$config->load('adm')
    ->set('devicedriver', array(
        'title' => 'devicedriver',
        'url' => 'devicedriver',
        'icon' => 'fa-sitemap',
        'order' => 50,
    ));

// Регистрируем маршруты для модуля
Route::set('devicedriver', 'devicedriver(/<action>)')
    ->defaults(array(
        'controller' => 'devicedriver',
        'action'     => 'index',
    ));
