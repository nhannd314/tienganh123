<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseEnrollmentController;
use App\Http\Controllers\CourseIndexController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonReviewController;
use App\Http\Controllers\MembershipEnrollmentController;
use App\Http\Controllers\PaymentStatusController;
use App\Http\Controllers\SepayWebhookController;
use App\Http\Middleware\EnsureLessonAccess;
use App\Http\Middleware\VerifySepayWebhook;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/khoa-hoc', CourseIndexController::class)->name('courses.index');
Route::get('/khoa-hoc/{course:slug}', CourseController::class)->name('courses.show');
Route::get('/khoa-hoc/{course:slug}/dang-ky', CourseEnrollmentController::class)->middleware('auth')->name('courses.enroll');
Route::get('/thanh-vien/dang-ky', MembershipEnrollmentController::class)->middleware('auth')->name('membership.enroll');
Route::get('/thanh-toan/{payment}/trang-thai', PaymentStatusController::class)->middleware('auth')->name('payments.status');
Route::get('/khoa-hoc/{course:slug}/{unit:slug}/{lesson:slug}', LessonController::class)
    ->scopeBindings()
    ->middleware(EnsureLessonAccess::class)
    ->name('lessons.show');
Route::get('/khoa-hoc/{course:slug}/{unit:slug}/{lesson:slug}/review/{step}', [LessonReviewController::class, 'show'])
    ->whereIn('step', ['1', '2', '3', '4', '5', '6'])
    ->scopeBindings()
    ->middleware(EnsureLessonAccess::class)
    ->name('lessons.review.show');
Route::post('/khoa-hoc/{course:slug}/{unit:slug}/{lesson:slug}/review/{step}', [LessonReviewController::class, 'store'])
    ->whereIn('step', ['1', '2', '3', '4', '5', '6'])
    ->scopeBindings()
    ->middleware(['auth', EnsureLessonAccess::class])
    ->name('lessons.review.store');
// Each call is a paid Azure request, hence the rate limit.
Route::post('/khoa-hoc/{course:slug}/{unit:slug}/{lesson:slug}/review/phat-am', [LessonReviewController::class, 'pronounce'])
    ->scopeBindings()
    ->middleware([EnsureLessonAccess::class, 'throttle:20,1'])
    ->name('lessons.review.pronounce');
Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/dang-nhap', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/dang-nhap/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/dang-nhap/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
    Route::get('/dang-ky', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/dang-ky', [RegisteredUserController::class, 'store'])->name('register.store');
});
Route::post('/dang-xuat', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::post('/webhooks/sepay', SepayWebhookController::class)
    ->withoutMiddleware(PreventRequestForgery::class)
    ->middleware(VerifySepayWebhook::class)
    ->name('webhooks.sepay');
Route::view('/demo', 'demo')->name('demo');
