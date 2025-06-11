<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlockchainApiService
{
    protected string $baseUrl = 'https://blockchain.info/';
    protected int $retries = 3;

    public function getTransactionCount(string $address): int
    {
        $url = $this->baseUrl . 'multiaddr?active=' . $address . '&limit=1';
        $response = $this->makeRequest($url);

        if (isset($response['addresses'][0]['n_tx'])) {
            return $response['addresses'][0]['n_tx'];
        }

        return 0;
    }

    public function getBalance(string $address): int
    {
        $url = $this->baseUrl . 'balance?active=' . $address;
        $response = $this->makeRequest($url);

        if (isset($response['addresses'][0]['final_balance'])) {
            return $response['addresses'][0]['final_balance'];
        }

        return 0;
    }

    public function getTransactions(string $address, int $limit = 50, int $offset = 0): array
    {
        $url = $this->baseUrl . 'multiaddr?active=' . $address . '&limit=' . $limit . '&offset=' . $offset;
        $response = $this->makeRequest($url);

        if (isset($response['txs'])) {
            Log::info('Transações recebidas da API', ['address' => $address, 'limit' => $limit, 'offset' => $offset, 'count' => count($response['txs'])]);
            return $response['txs'];
        }

        Log::warning('Nenhuma transação recebida da API', ['address' => $address, 'limit' => $limit, 'offset' => $offset]);
        return [];
    }

    public function saveTransaction(Wallet $wallet, array $tx): void
    {
        if (!isset($tx['hash'])) {
            Log::error('Transação sem hash definida', ['wallet_id' => $wallet->id, 'txData' => $tx]);
            return;
        }

        $blockTime = isset($tx['time']) ? Carbon::createFromTimestamp($tx['time']) : null;
        if ($blockTime && $blockTime->year < 2009) {
            Log::warning('Data de transação inválida, usando data atual', ['wallet_id' => $wallet->id, 'tx_hash' => $tx['hash'], 'block_time' => $blockTime]);
            $blockTime = now();
        }

        $type = $this->determineTransactionType($wallet, $tx);
        $address = $this->extractAddress($wallet, $tx, $type);
        $value = $this->calculateValue($wallet, $tx, $type);

        $transactionData = [
            'wallet_id' => $wallet->id,
            'tx_hash' => $tx['hash'],
            'type' => $type,
            'address' => $address,
            'value' => $value,
            'block_time' => $blockTime,
        ];

        try {
            $exists = Transaction::where('wallet_id', $wallet->id)
                ->where('tx_hash', $tx['hash'])
                ->where('type', $type)
                ->where('address', $address)
                ->exists();

            if (!$exists) {
                Transaction::create($transactionData);
                Log::info('Transação salva com sucesso', ['wallet_id' => $wallet->id, 'tx_hash' => $tx['hash']]);
            } else {
                Log::info('Transação já existe, pulando', ['wallet_id' => $wallet->id, 'tx_hash' => $tx['hash']]);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao salvar transação', ['wallet_id' => $wallet->id, 'tx_hash' => $tx['hash'], 'error' => $e->getMessage()]);
        }
    }

    protected function makeRequest(string $url, int $attempt = 1)
    {
        try {
            // Delay adicional para rate limiting
            if ($attempt > 1) {
                $delay = pow(2, $attempt - 1) * 10; // 10s, 20s, 40s
                Log::info('Aguardando antes de nova tentativa', ['url' => $url, 'attempt' => $attempt, 'delay' => $delay]);
                sleep($delay);
            }
            
            $response = Http::timeout(60)->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            if ($response->status() === 429 && $attempt <= $this->retries) {
                $delay = pow(2, $attempt - 1) * 30; // Backoff exponencial: 30s, 60s, 120s
                Log::warning('Limite de taxa atingido, aguardando antes de nova tentativa', ['url' => $url, 'attempt' => $attempt, 'delay' => $delay]);
                sleep($delay);
                return $this->makeRequest($url, $attempt + 1);
            }

            Log::error('Erro ao fazer requisição para a API', ['url' => $url, 'status' => $response->status(), 'body' => $response->body()]);
            return [];
        } catch (\Exception $e) {
            Log::error('Exceção ao fazer requisição para a API', ['url' => $url, 'error' => $e->getMessage()]);
            if ($attempt <= $this->retries) {
                $delay = pow(2, $attempt - 1) * 15; // 15s, 30s, 60s
                Log::warning('Erro de conexão, aguardando antes de nova tentativa', ['url' => $url, 'attempt' => $attempt, 'delay' => $delay]);
                sleep($delay);
                return $this->makeRequest($url, $attempt + 1);
            }
            return [];
        }
    }

    protected function determineTransactionType(Wallet $wallet, array $transaction): string
    {
        $inputs = collect($transaction['inputs']);
        $outputs = collect($transaction['out']);

        $isSender = $inputs->contains(function ($input) use ($wallet) {
            return isset($input['prev_out']['addr']) && $input['prev_out']['addr'] === $wallet->address;
        });

        $isRecipient = $outputs->contains(function ($output) use ($wallet) {
            return isset($output['addr']) && $output['addr'] === $wallet->address;
        });

        if ($isSender && $isRecipient) {
            $inputValue = $inputs->sum(function ($input) use ($wallet) {
                return isset($input['prev_out']['addr']) && $input['prev_out']['addr'] === $wallet->address ? $input['prev_out']['value'] : 0;
            });
            $outputValue = $outputs->sum(function ($output) use ($wallet) {
                return isset($output['addr']) && $output['addr'] === $wallet->address ? $output['value'] : 0;
            });

            return $outputValue > $inputValue ? 'receive' : 'send';
        }

        return $isSender ? 'send' : 'receive';
    }

    protected function getSenderAddress(array $transaction): string
    {
        $inputs = collect($transaction['inputs']);
        $sender = $inputs->first(function ($input) {
            return isset($input['prev_out']['addr']);
        });
        return $sender['prev_out']['addr'] ?? 'unknown';
    }

    protected function getRecipientAddress(array $transaction, string $walletAddress): string
    {
        $outputs = collect($transaction['out']);
        $recipient = $outputs->first(function ($output) use ($walletAddress) {
            return isset($output['addr']) && $output['addr'] !== $walletAddress;
        });
        return $recipient['addr'] ?? 'unknown';
    }

    protected function getTransactionValue(string $walletAddress, array $transaction, string $type): int
    {
        if ($type === 'send') {
            $inputs = collect($transaction['inputs']);
            return $inputs->sum(function ($input) use ($walletAddress) {
                return isset($input['prev_out']['addr']) && $input['prev_out']['addr'] === $walletAddress ? $input['prev_out']['value'] : 0;
            });
        }

        $outputs = collect($transaction['out']);
        return $outputs->sum(function ($output) use ($walletAddress) {
            return isset($output['addr']) && $output['addr'] === $walletAddress ? $output['value'] : 0;
        });
    }

    protected function extractAddress(Wallet $wallet, array $transaction, string $type): string
    {
        if ($type === 'receive') {
            return $this->getSenderAddress($transaction);
        } else {
            return $this->getRecipientAddress($transaction, $wallet->address);
        }
    }

    protected function calculateValue(Wallet $wallet, array $transaction, string $type): int
    {
        if ($type === 'send') {
            return $this->getTransactionValue($wallet->address, $transaction, $type);
        } else {
            return $this->getTransactionValue($wallet->address, $transaction, $type);
        }
    }
} 