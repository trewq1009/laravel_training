<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});


// 회원가입
Route::get('/register', function() {
    return view('member.join');
})->middleware('guest');
Route::post('/register', [\App\Http\Controllers\MemberController::class, 'create'])->middleware('guest');

// 로그인
Route::get('/login', function() {
   return view('member.login');
})->middleware('guest');
Route::post('/login', [\App\Http\Controllers\MemberController::class, 'login'])->middleware('guest');











// php artisan make:controller MemberController --resource
Route::resource('member', \App\Http\Controllers\MemberController::class);

// 아무것도 매칭 되지 않을때 다시 홈
Route::fallback(function () {
    return view('welcome');
});
