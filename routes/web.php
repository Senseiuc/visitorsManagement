<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Http\Controllers\VisitorCheckinController;

Route::get('/', function () {
    return view('welcome');
});

// Public visitor self check-in routes
Route::get('/visitor', [VisitorCheckinController::class, 'showLookup'])->name('visitor.lookup');
Route::post('/visitor', [VisitorCheckinController::class, 'postLookup'])->name('visitor.postLookup');
Route::get('/visitor/existing/{visitor}', [VisitorCheckinController::class, 'showExisting'])->name('visitor.existing');
Route::get('/visitor/new', [VisitorCheckinController::class, 'showNew'])->name('visitor.new');
Route::post('/visitor/checkin', [VisitorCheckinController::class, 'postCheckin'])->name('visitor.checkin');
Route::get('/visitor/success', [VisitorCheckinController::class, 'success'])->name('visitor.success');

// Securely serve local storage files limited to the 'imports' directory
Route::get('/files/local', function (Request $request) {
    if (! auth()->check()) {
        abort(403);
    }
    $user = auth()->user();
    if (! ($user->isSuperAdmin() || $user->hasPermission('users.create'))) {
        abort(403);
    }

    $path = (string) $request->query('path', '');
    if ($path === '' || str_contains($path, '..')) {
        abort(400, 'Invalid path');
    }

    // Only allow downloads from the imports directory
    if (! str_starts_with($path, 'imports/')) {
        abort(403);
    }

    $disk = Storage::disk('local');
    if (! $disk->exists($path)) {
        abort(404);
    }

    return response()->download($disk->path($path));
})->name('files.local');
