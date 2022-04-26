<?php
// GETやPOSTでのリクエストが飛んできたら、どこのメソッドに飛ばすかなどをHomeController.phpへルーティングしてあげるファイル

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

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



Auth::routes();

Route::get('/', [HomeController::class, 'index'])->name('index');
Route::get('/home', [HomeController::class, 'index'])->name('home');

//第一引数にURL、第二引数に行くところを記述　ルーティングの名前を任意で決める、name('ルーティング名')　リダイレクトに使うのがこのルーティング名、URL変わってもルーティング名はそのまま
//イメージは/storeにPOSTとしてリクエストが飛んできたら、HomeContのクラスのstoreメソッドに行くというルートを通している
Route::post('/store', [HomeController::class, 'store'])->name('store');

//編集を押したとこのメモの主キー、idをURLに指定、そのidの値利用する
Route::get('/edit/{id}', [HomeController::class, 'edit'])->name('edit');

Route::post('/update', [HomeController::class, 'update'])->name('update');
Route::post('/destory', [HomeController::class, 'destory'])->name('destory');