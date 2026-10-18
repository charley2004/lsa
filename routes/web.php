<?php

use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\BookmakerOfferController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StaticController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [StaticController::class, 'about'])->name('about');
Route::get('/analysis', [AnalysisController::class, 'index'])->name('analysis');
Route::get('/results', [ResultsController::class, 'index'])->name('results');
Route::get('/disclaimer', [StaticController::class, 'disclaimer'])->name('disclaimer');
Route::get('/privacy-policy', [StaticController::class, 'privacy'])->name('privacy');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

// Affiliate click-through — counts the click, then redirects to the real bookmaker URL.
Route::get('/go/{slug}', [BookmakerOfferController::class, 'go'])->name('offer.go');
