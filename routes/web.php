<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SSRFDemoController;

//Route::get('/', function () {
//    return view('welcome');
//});


Route::get('/ssrf-test', [SSRFDemoController::class, 'index'])->name('ssrf.test.page');


Route::get('/fetch-url', [SSRFDemoController::class, 'fetchUrl'])->name('ssrf.fetch');
Route::get('/fetch-url-secure', [SSRFDemoController::class, 'fetchUrlSecure'])->name('ssrf.fetch.secure');
