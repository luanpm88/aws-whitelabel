<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::group(['middleware' => ['web'], 'namespace' => 'Acelle\AwsWhitelabel\Controllers'], function() {
    // White label setting page
    Route::match(['get', 'post'], '/aws-whitelabel', 'AwsWhitelabelController@index');
    
});