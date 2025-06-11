<div>
    <h3>Progresso de Importação de Transações</h3>
    @if($this->hasEventData())
        @foreach($this->eventData as $data)
            <div>
                <p>Carteira: {{ $data['address'] }}</p>
                <p>Progresso: {{ number_format($data['progress'], 2) }}%</p>
                <p>Transações Importadas: {{ $data['imported_transactions'] }} de {{ $data['total_transactions'] }}</p>
                <div style="width: 100%; background-color: #e9ecef; border-radius: 5px; overflow: hidden;">
                    <div style="width: {{ $data['progress'] }}%; background-color: #007bff; height: 20px;"></div>
                </div>
            </div>
        @endforeach
    @else
        <p>Nenhuma importação em andamento.</p>
    @endif
</div> 