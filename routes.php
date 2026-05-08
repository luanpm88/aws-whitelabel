<?php

use Illuminate\Support\Facades\Route;

// Plugin's own icon — public, served from plugin folder. Works whether the
// plugin is copied into storage/app/plugins/ or symlinked there for dev.
Route::get('plugins/acelle/awswhitelabel/icon.png', function () {
    $candidates = [
        __DIR__.'/assets/image/icon.png',
        __DIR__.'/icon.jpg',
    ];
    foreach ($candidates as $path) {
        if (file_exists($path)) {
            return response()->file($path, [
                'Content-Type' => str_ends_with($path, '.png') ? 'image/png' : 'image/jpeg',
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }
    }
    abort(404);
})->name('plugin.acelle.awswhitelabel.icon');

// Settings — admin-only. Matches the standard Acelle admin middleware stack
// (see routes/web.php line 885: 'not_installed', 'auth', 'backend', '2fa').
Route::group([
    'middleware' => ['web', 'not_installed', 'auth', 'backend', '2fa'],
    'namespace' => '\Acelle\Plugin\AwsWhitelabel\Controllers',
    'prefix' => 'plugins/acelle/awswhitelabel',
], function () {
    Route::get('/', 'MainController@index')->name('plugin.awswhitelabel.index');
    Route::get('/edit-key', 'MainController@editKey')->name('plugin.awswhitelabel.edit_key');
    Route::get('/select-domain', 'MainController@selectDomain')->name('plugin.awswhitelabel.select_domain');
    Route::post('/save-key', 'MainController@saveKey')->name('plugin.awswhitelabel.save_key');
    Route::post('/save-domain', 'MainController@saveDomain')->name('plugin.awswhitelabel.save_domain');
    Route::post('/test-connection', 'MainController@testConnection')->name('plugin.awswhitelabel.test_connection');
    Route::post('/disconnect', 'MainController@disconnect')->name('plugin.awswhitelabel.disconnect');
});
