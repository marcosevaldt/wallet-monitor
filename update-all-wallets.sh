#!/bin/bash

echo "🔄 Atualizando transações de todas as carteiras..."
echo ""

# Verificar se o Laravel está configurado
if [ ! -f ".env" ]; then
    echo "❌ Arquivo .env não encontrado. Certifique-se de estar no diretório do projeto."
    exit 1
fi

# Executar o comando de atualização
php artisan wallet:update-transactions --all

echo ""
echo "✅ Atualização concluída!"
echo ""
echo "💡 Dicas:"
echo "   - Monitore os logs em storage/logs/laravel.log"
echo "   - Verifique o status das filas com: php artisan queue:work"
echo "   - Para atualizar uma carteira específica: php artisan wallet:update-transactions [ID]" 