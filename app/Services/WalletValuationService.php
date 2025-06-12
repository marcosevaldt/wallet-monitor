<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\BitcoinPriceHistory;
use Carbon\Carbon;

class WalletValuationService
{
    /**
     * Calcula a valorização/desvalorização da carteira baseada na variação do preço do Bitcoin.
     * Considera o preço médio de compra vs preço atual.
     *
     * @param Wallet $wallet
     * @return array|null
     */
    public function calcularValorizacao(Wallet $wallet): ?array
    {
        $transacoes = $wallet->transactions()->orderBy('block_time')->get();
        $totalBTCComprado = 0;
        $totalUSDInvestido = 0;
        $totalBTCVendido = 0;
        $totalUSDRecebido = 0;

        foreach ($transacoes as $tx) {
            if (!$tx->block_time) continue;
            $data = Carbon::parse($tx->block_time);
            $preco = BitcoinPriceHistory::getClosingPriceAtDate($data);
            if (!$preco) continue;

            $valorBTC = $tx->value / 100000000; // satoshis para BTC

            if ($tx->type === 'receive') {
                $totalBTCComprado += $valorBTC;
                $totalUSDInvestido += $valorBTC * $preco;
            } elseif ($tx->type === 'send') {
                $totalBTCVendido += $valorBTC;
                $totalUSDRecebido += $valorBTC * $preco;
            }
        }

        // BTC líquido na carteira (comprado - vendido)
        $btcLiquido = $totalBTCComprado - $totalBTCVendido;
        
        if ($btcLiquido <= 0) {
            return null; // Carteira vazia ou só vendas
        }

        // Preço médio de compra
        $precoMedioCompra = $totalUSDInvestido / $totalBTCComprado;
        
        // Preço atual do Bitcoin
        $precoAtual = BitcoinPriceHistory::getLatestPrice();
        if (!$precoAtual) {
            return null;
        }

        // Valorização/desvalorização
        $variacaoPreco = $precoAtual - $precoMedioCompra;
        $valorizacaoPercentual = ($precoMedioCompra > 0) ? ($variacaoPreco / $precoMedioCompra) * 100 : 0;
        
        // Valor atual da carteira
        $valorAtual = $btcLiquido * $precoAtual;
        
        // Valor se vendesse pelo preço médio de compra
        $valorPrecoMedio = $btcLiquido * $precoMedioCompra;
        
        // Lucro/prejuízo em USD
        $lucroPrejuizo = $valorAtual - $valorPrecoMedio;

        return [
            'btc_liquido' => $btcLiquido,
            'preco_medio_compra' => $precoMedioCompra,
            'preco_atual' => $precoAtual,
            'variacao_preco' => $variacaoPreco,
            'valorizacao_percentual' => $valorizacaoPercentual,
            'valor_atual' => $valorAtual,
            'valor_preco_medio' => $valorPrecoMedio,
            'lucro_prejuizo' => $lucroPrejuizo,
        ];
    }
}
