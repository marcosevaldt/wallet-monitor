<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Cabeçalho da Carteira --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $this->wallet->name }}</h1>
                    <p class="text-gray-600 mt-1">{{ $this->wallet->address }}</p>
                    @if($this->wallet->label)
                        <p class="text-sm text-gray-500 mt-1">Rótulo: {{ $this->wallet->label }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-gray-900">
                        {{ number_format(($this->wallet->balance ?? 0) / 100000000, 8) }} BTC
                    </div>
                    <div class="text-sm text-gray-600">Saldo Atual</div>
                </div>
            </div>
        </div>

        {{-- Grid de Métricas Principais --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Status da Importação --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @if($this->wallet->import_progress == 100)
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        @elseif($this->wallet->import_progress > 0)
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        @else
                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900">Status Importação</p>
                        <p class="text-sm text-gray-600">
                            @if($this->wallet->import_progress == 100)
                                Concluída
                            @elseif($this->wallet->import_progress > 0)
                                Em Andamento
                            @else
                                Não Iniciada
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Transações --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900">Transações</p>
                        <p class="text-sm text-gray-600">{{ $this->wallet->imported_transactions ?? 0 }} / {{ $this->wallet->total_transactions ?? 0 }}</p>
                    </div>
                </div>
            </div>

            {{-- Última Importação --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900">Última Importação</p>
                        <p class="text-sm text-gray-600">
                            @php
                                $lastImport = $this->wallet->last_import_at;
                            @endphp
                            @if($lastImport)
                                {{ $lastImport->format('d/m/Y H:i') }}
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Valorização --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @if($this->valorizacao && $this->valorizacao['valorizacao_percentual'] >= 0)
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        @elseif($this->valorizacao && $this->valorizacao['valorizacao_percentual'] < 0)
                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1v-5a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586l-4.293-4.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        @else
                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900">Valorização</p>
                        <p class="text-sm {{ $this->valorizacao && $this->valorizacao['valorizacao_percentual'] >= 0 ? 'text-green-600' : ($this->valorizacao && $this->valorizacao['valorizacao_percentual'] < 0 ? 'text-red-600' : 'text-gray-600') }}">
                            @if($this->valorizacao)
                                @php
                                    $valor = $this->valorizacao['valorizacao_percentual'];
                                    $sinal = $valor >= 0 ? '+' : '';
                                @endphp
                                {{ $sinal }}{{ number_format($valor, 2) }}%
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Seção de Análise de Valorização --}}
        @if($this->valorizacao)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Análise de Valorização</h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Métricas Principais --}}
                <div>
                    <h3 class="text-md font-medium text-gray-900 mb-3">Métricas Principais</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">BTC Líquido</span>
                            <span class="text-sm font-semibold text-gray-900">{{ number_format($this->valorizacao['btc_liquido'], 8) }} BTC</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">Valor Atual</span>
                            <span class="text-sm font-semibold text-gray-900">${{ number_format($this->valorizacao['valor_atual'], 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">Lucro/Prejuízo</span>
                            <span class="text-sm font-semibold {{ $this->valorizacao['lucro_prejuizo'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                ${{ number_format($this->valorizacao['lucro_prejuizo'], 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Análise de Preços --}}
                <div>
                    <h3 class="text-md font-medium text-gray-900 mb-3">Análise de Preços</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">Preço Médio de Compra</span>
                            <span class="text-sm font-semibold text-gray-900">${{ number_format($this->valorizacao['preco_medio_compra'], 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">Preço Atual do Bitcoin</span>
                            <span class="text-sm font-semibold text-gray-900">${{ number_format($this->valorizacao['preco_atual'], 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">Variação do Preço</span>
                            <span class="text-sm font-semibold {{ $this->valorizacao['variacao_preco'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                ${{ number_format($this->valorizacao['variacao_preco'], 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Análise de Valorização Indisponível</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>Para visualizar a análise de valorização, é necessário importar as transações da carteira primeiro.</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Área para Futuros Gráficos --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Gráficos e Análises</h2>
            <div class="bg-gray-50 rounded-lg p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Gráficos em Desenvolvimento</h3>
                <p class="mt-1 text-sm text-gray-500">Esta área será utilizada para exibir gráficos de valorização, histórico de preços e outras análises visuais.</p>
            </div>
        </div>
    </div>
</x-filament-panels::page> 