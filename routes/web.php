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

Route::controller(\App\Http\Controllers\AuthController::class)->group(function() {
    Route::middleware('guest')->group(function() {
        Route::get('/register', function() {return view('auth.join'); });    // 회원 가입
        Route::post('/register', 'create');
        Route::get('/login', function() {return view('auth.login'); })->name('login');
        Route::post('/login', 'login');
    });
    Route::middleware('auth')->group(function() {
        Route::get('/logout', 'logout');                                    // 로그 아웃
        Route::get('/profile', 'profile')->name('profile');           // 프로필
        Route::post('/profile', 'update');
        Route::post('/delete', 'delete');                                   // 회원 탈퇴
    });
});

// 마일리지 충전
Route::controller(\App\Http\Controllers\PaymentController::class)->group(function() {
    Route::middleware('auth')->group(function() {
        Route::get('/payment/step1', function() {return view('payment.step1'); })->name('payment');
        Route::get('/payment/step2', 'method');
        Route::post('/payment/send', 'send');
    });
});

// 마일리지 출금
Route::controller(\App\Http\Controllers\MileageController::class)->group(function() {
    Route::middleware('auth')->group(function() {
        Route::get('/withdrawal', 'withdrawal')->name('withdrawal');    // 마일리지 출금
        Route::post('withdrawal', 'withdrawalAction');
        Route::get('/mileageReport', 'report')->name('mileageReport');  // 마일리지 사용내역
    });
});

// 거래
Route::controller(\App\Http\Controllers\TradeController::class)->group(function() {
    Route::middleware('auth')->group(function() {
        Route::get('/trade', 'list')->name('trade');
        Route::get('/trade/registration', function() {return view('trade.registration'); });
        Route::post('/trade/registration', 'insert');
        Route::get('/trade/detail/{no}', 'detail');
        Route::post('/trade/detail/{no}', 'trading');
        Route::delete('/trade/detail/{no}', 'delete');
        Route::get('/trade/list/{method}', 'tradeList');
        Route::post('/trade/list/cancel', 'cancel');
        Route::post('/trade/list/success', 'success');
    });
});

// 방명록
Route::controller(\App\Http\Controllers\VisitorsController::class)->group(function() {
    Route::get('/visitors', 'list')->name('visitors');
    Route::post('/visitors', 'insert');
});

Route::controller(\App\Http\Controllers\AjaxController::class)->group(function() {
    Route::get('/ajax/visitors/list', 'visitorsList');
    Route::post('/ajax/visitors/comment', 'visitorsComment');
    Route::post('/ajax/visitors/delete', 'visitorsDelete');
    Route::post('/ajax/visitors/update', 'visitorsUpdate');

    Route::middleware('auth')->group(function() {
        Route::post('/ajax/payment', 'payment');
        Route::post('/ajax/payment/result', 'paymentInsert');
    });
});

// 메일
Route::controller(\App\Http\Controllers\MailController::class)->group(function() {
    Route::middleware('guest')->group(function() {
        Route::get('/email/{hash}', 'signMail');
    });
});

// 관리자
Route::controller(\App\Http\Controllers\AdminController::class)->group(function() {
    Route::middleware('guest')->group(function() {
        Route::get('/admin', function() {return view('admin.home'); })->withoutMiddleware('guest')->name('admin');
        Route::get('/admin/register', function() {return view('admin.auth.register'); });
        Route::post('/admin/register', 'sign');
        Route::get('/admin/login', function() {return view('admin.auth.login'); });
        Route::post('/admin/login', 'login');
    });
    Route::middleware('auth')->group(function() {
        Route::get('/admin/logout', 'logout');
        Route::get('/admin/member/list', 'list');
        Route::get('/admin/member/list/{no}', 'detail');

        Route::get('/admin/withdrawal/list', 'withdrawalList');
        Route::get('/admin/withdrawal/detail/{no}', 'withdrawalDetail');
        Route::post('/admin/withdrawal/detail/{no}', 'withdrawalAction');
    });
});


// php artisan make:controller MemberController --resource
//Route::resource('auth', \App\Http\Controllers\MemberController::class);

// 아무것도 매칭 되지 않을때 다시 홈
Route::fallback(function() {return view('welcome'); });
