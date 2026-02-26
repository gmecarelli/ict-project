<?php

use Illuminate\Support\Facades\Route;
use Packages\IctInterface\Controllers\Auth\IctAuthController;


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

Route::middleware(['web'])->group(function () {
    Route::get('/', [IctAuthController::class, 'login'])->name('auth.login');
    Route::get('/login', [IctAuthController::class, 'login'])->name('auth.login');

    Route::post('/check', [IctAuthController::class, 'check'])->name('auth.check');
    Route::any('/logout', [IctAuthController::class, 'logout'])->name('auth.logout');
});


//root per esempio PDF
Route::prefix('pdf')->get('/curldrs', ['Packages\IctInterface\Controllers\PDFController', 'curlCreateDrs'])->name('curl.pdf.drs');

Route::middleware(['web', 'islogged'])->group(function () {
    Route::get('/dashboard', [IctAuthController::class, 'dashboard'])->name('dashboard');
    Route::resource('/menu', 'Packages\IctInterface\Controllers\MenuController')->name('DELETE', 'delete');
    Route::resource('/report', 'Packages\IctInterface\Controllers\ReportController');
    Route::resource('/reportcol', 'Packages\IctInterface\Controllers\ReportColumnController');
    Route::resource('/profiles', 'Packages\IctInterface\Controllers\ProfileController');
    Route::resource('/roles', 'Packages\IctInterface\Controllers\ProfileRoleController');
    Route::resource('/options', 'Packages\IctInterface\Controllers\OptionController');


    Route::get('/deleteattach', ['Packages\IctInterface\Controllers\AttachmentController', 'delete'])->name('delete.attachments');

    Route::resource('/form', 'Packages\IctInterface\Controllers\FormController');
    Route::resource('/formfield', 'Packages\IctInterface\Controllers\FormFieldController');

    /**
     * SEARCH
     */
    Route::prefix('search')->group(function () {
        Route::get('/users', ['Packages\IctInterface\Controllers\Ajax\AjaxController', 'searchUsers'])->name('call.search.users');
    });
    /**
     * MODAL
     */
    Route::prefix('modal')->group(function () {
        Route::post('/savecol', ['Packages\IctInterface\Controllers\Ajax\AjaxController', 'saveReportColsForm'])->name('call.save.reportcols');
        Route::post('/saveformitem', ['Packages\IctInterface\Controllers\Ajax\AjaxController', 'saveFormItemsForm'])->name('call.save.formitems');
        Route::post('/saverole', ['Packages\IctInterface\Controllers\Ajax\AjaxController', 'saveFormRole'])->name('call.save.role');
        Route::post('/addusers', ['Packages\IctInterface\Controllers\ProfileController', 'addUsers'])->name('call.add.users');
    });
    /**
     * EXPORT
     */
    Route::prefix('export')->group(function () {
        Route::get('report', [Packages\IctInterface\Controllers\ExcelController::class, 'exportReport'])->name('export.report');
        Route::get('reportcol', [Packages\IctInterface\Controllers\ExcelController::class, 'exportReportCols'])->name('export.reportcol');
        Route::get('form', [Packages\IctInterface\Controllers\ExcelController::class, 'exportForm'])->name('export.form');
        Route::get('formfield', [Packages\IctInterface\Controllers\ExcelController::class, 'exportFormFields'])->name('export.formfield');
        Route::get('roles', [Packages\IctInterface\Controllers\ExcelController::class, 'exportProfileRoles'])->name('export.roles');
    });
});
