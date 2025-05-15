<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

Route::get('/internal/secret-data', function () {
    return response()->json([
        'db_password' => 'supersecret123',
        'api_key' => 'XYZ-987654321',
        'admin_email' => 'admin@empresa.com'
    ]);
});
