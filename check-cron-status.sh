#!/bin/bash

echo "🔍 Verificando status das Cronjobs do Bitcoin Monitor"
echo "=================================================="

echo ""
echo "📋 Cronjobs configuradas no sistema:"
crontab -l

echo ""
echo "📅 Próximas execuções agendadas:"
php artisan schedule:list

echo ""
echo "📊 Logs de execução:"
echo "-------------------"

if [ -f "storage/logs/bitcoin-update.log" ]; then
    echo "✅ Log de atualização diária (últimas 5 linhas):"
    tail -n 5 storage/logs/bitcoin-update.log
else
    echo "❌ Log de atualização diária não encontrado"
fi

echo ""

if [ -f "storage/logs/bitcoin-update-recent.log" ]; then
    echo "✅ Log de atualização recente (últimas 5 linhas):"
    tail -n 5 storage/logs/bitcoin-update-recent.log
else
    echo "❌ Log de atualização recente não encontrado"
fi

echo ""

if [ -f "storage/logs/bitcoin-historical.log" ]; then
    echo "✅ Log de dados históricos (últimas 5 linhas):"
    tail -n 5 storage/logs/bitcoin-historical.log
else
    echo "❌ Log de dados históricos não encontrado"
fi

echo ""
echo "📈 Status dos dados:"
echo "-------------------"
php artisan tinker --execute="echo 'Registros diários: ' . App\Models\BitcoinPriceHistory::where('is_daily', true)->count(); echo PHP_EOL; echo 'Última atualização: '; \$last = App\Models\BitcoinPriceHistory::where('is_daily', true)->latest('timestamp')->first(); if(\$last) { echo \$last->timestamp->format('d/m/Y H:i:s'); } else { echo 'Nenhum registro encontrado'; }"

echo ""
echo "✅ Verificação concluída!" 