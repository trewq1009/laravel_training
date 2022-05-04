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
Route::get('/register', function() {return view('auth.join'); })->middleware('guest');
Route::post('/register', [\App\Http\Controllers\AuthController::class, 'create'])->middleware('guest');

// 로그인
Route::get('/login', function() {return view('auth.login'); })->middleware('guest')->name('login');
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->middleware('guest');

// 로그아웃
Route::get('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->middleware('auth');

// 프로필
Route::get('/profile', [\App\Http\Controllers\AuthController::class, 'profile'])->middleware('auth')->name('profile');
Route::post('/profile', [\App\Http\Controllers\AuthController::class, 'update'])->middleware('auth');

// 마일리지 충전
Route::get('/payment/step1', function() {return view('payment.step1'); })->middleware('auth')->name('payment');
Route::get('/payment/step2', [\App\Http\Controllers\PaymentController::class, 'method'])->middleware('auth');
Route::post('/payment/credit', [\App\Http\Controllers\PaymentController::class, 'credit'])->middleware('auth');
Route::post('/payment/phone', [\App\Http\Controllers\PaymentController::class, 'phone'])->middleware('auth');
Route::post('/payment/voucher', [\App\Http\Controllers\PaymentController::class, 'voucher'])->middleware('auth');

// 마일리지 출금
Route::get('/withdrawal', [\App\Http\Controllers\MileageController::class, 'withdrawal'])->middleware('auth')->name('withdrawal');
Route::post('/withdrawal', [\App\Http\Controllers\MileageController::class, 'withdrawalAction'])->middleware('auth');

// 마일리지 사용내역
Route::get('/mileageReport', [\App\Http\Controllers\MileageController::class, 'report'])->middleware('auth')->name('mileageReport');

// 거래





// php artisan make:controller MemberController --resource
//Route::resource('auth', \App\Http\Controllers\MemberController::class);

// 아무것도 매칭 되지 않을때 다시 홈
Route::fallback(function() {return view('welcome'); });
