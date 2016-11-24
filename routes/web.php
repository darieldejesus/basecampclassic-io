<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'ProjectController@getActived');

Route::get('/onhold', 'ProjectController@getOnHold');

Route::get('/archived', 'ProjectController@getArchived');

Route::get('/users', 'UserController@getUsers');

Route::get('/settings', 'SettingController@getSettings');
