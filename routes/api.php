<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Wallet;
use App\Models\Transaction;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rota para buscar progresso da importação
Route::get('/import-progress/{walletId}', function ($walletId) {
    $wallet = Wallet::find($walletId);
    
    if (!$wallet) {
        return response()->json(['error' => 'Carteira não encontrada'], 404);
    }

    // Buscar dados da importação (você pode implementar um cache ou tabela para isso)
    $totalTransactions = Transaction::where('wallet_id', $walletId)->count();
    
    return response()->json([
        'wallet_id' => $walletId,
        'total_transactions' => $totalTransactions,
        'status' => 'processing', // Você pode implementar lógica mais sofisticada
        'message' => "Total de transações: {$totalTransactions}",
        'timestamp' => now()->toISOString(),
    ]);
}); 