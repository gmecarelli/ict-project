<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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

require base_path('packages/IctInterface/src/routes.php');

Route::middleware(['web', 'islogged'])->group(function () {
    Route::resource('/books', BookController::class);
    Route::resource('/authors', BookController::class);

    Route::prefix('modal')->group(function () {
        Route::get('/loadbook', [BookController::class, 'loadModal'])->name('load.books');
        Route::post('/savebook', [BookController::class, 'saveModal'])->name('save.books');

    });

    Route::prefix('export')->group(function () {
        Route::get('books', [BookController::class, 'exportBooks'])->name('export.books');
        Route::get('authors', [AuthorController::class, 'exportAuthors'])->name('export.authors');
    });

    /**
     * FINDER
     * Route di ricerca per campi autocomplete con classe "finder".
     * Ogni route riceve ?query=... e restituisce JSON:
     * { "result": "success", "items": [{ "display": "...", "values": { "key": "val", ... } }] }
     */
    Route::prefix('finder')->group(function () {
        Route::get('/options', function () {
            $query = request('query', '');
            $results = DB::table('options')
                ->where('label', 'like', '%' . $query . '%')
                ->where('is_enabled', 1)
                ->limit(20)
                ->get();

            $items = $results->map(fn($row) => [
                'display' => $row->label . ' [' . $row->code . ']',
                'values' => [
                    'label' => $row->label,
                    'code' => $row->code,
                ]
            ])->toArray();

            return response()->json([
                'result' => 'success',
                'items' => $items,
            ]);
        })->name('findOptions');
    });
});
