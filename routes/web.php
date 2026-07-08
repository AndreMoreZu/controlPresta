<?php

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/nuevo', [ClienteController::class, 'create'])->name('clientes.create');
    Route::get('/clientes/inactivos', [ClienteController::class, 'inactivos'])->name('clientes.inactivos');
    Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
    Route::get('/clientes/{cliente}', [ClienteController::class, 'show'])->name('clientes.show');
    Route::get('/clientes/{cliente}/editar', [ClienteController::class, 'edit'])->name('clientes.edit');
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
    Route::patch('/clientes/{cliente}/desactivar', [ClienteController::class, 'desactivar'])->name('clientes.desactivar');
    Route::patch('/clientes/{cliente}/reactivar', [ClienteController::class, 'reactivar'])->name('clientes.reactivar');

    Route::get('/clientes/{cliente}/prestamos/{prestamo}', [PrestamoController::class, 'show'])->name('prestamos.show');
    Route::get('/clientes/{cliente}/nuevo-prestamo',       [PrestamoController::class, 'create'])->name('prestamos.create');
    Route::post('/clientes/{cliente}/nuevo-prestamo',      [PrestamoController::class, 'store'])->name('prestamos.store');

    Route::get('/clientes/{cliente}/pago',    [PagoController::class, 'create'])->name('pagos.create');
    Route::post('/clientes/{cliente}/pago',   [PagoController::class, 'store'])->name('pagos.store');
    Route::get('/clientes/{cliente}/saldar',  [PagoController::class, 'saldoCreate'])->name('pagos.saldar');
    Route::post('/clientes/{cliente}/saldar', [PagoController::class, 'saldoStore'])->name('pagos.saldar.store');
});

require __DIR__.'/auth.php';
