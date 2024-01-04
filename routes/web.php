<?php

use App\Http\Middleware\EnsureSessionExist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function (Request $request) {
    return view('dashboard');
})->middleware(['auth', 'verified', EnsureSessionExist::class])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('callback', function (Request $request) {
    $http = Http::withToken($request->token)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->post(env('APP_ACCOUNT_URL').'/api/authorize-app');

    if (!$http->successful()) {
        dd($http->body());
        return redirect(env('APP_ACCOUNT_URL').'/login/?app='.env('APP_ACCOUNT_ID').'&secret='.env('APP_ACCOUNT_SECRET'));
    }


    User::updateOrCreate([
        'user_sso_id' => $http->collect('user')->get('id')
    ], [
        'payload' => $http->collect('user')->toArray(),
    ]);

    // $user = User::find($http->collect('user')->get('id'));
    Auth::loginUsingId($http->collect('user')->get('id'));

    $request->session()->regenerate();

    $request->session()->save();
    DB::connection('account')->table(config('session.table'))->where('id', session()->getId())->update(['group' =>  base64_decode($request->group)]);

    // $lastActivity = DB::connection('account')->table('sessions')->where('user_id', auth()->user()->id)->orderBy('last_activity')->first()?->last_activity;
    // DB::connection('account')->table('sessions')->where('user_id', auth()->user()->id)->update(['last_activity' => $lastActivity ?: strtotime(now())]);

    return to_route('dashboard');
});

require __DIR__.'/auth.php';
