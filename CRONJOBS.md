# 🔄 Cronjobs do Bitcoin Monitor

Este documento descreve as cronjobs configuradas para manter os dados do Bitcoin atualizados automaticamente.

## 📋 Cronjobs Configuradas

### 1. **Atualização Diária Completa**
- **Comando**: `php artisan bitcoin:populate-historical-data --days=7 --force`
- **Frequência**: Diariamente às 08:00
- **Log**: `storage/logs/bitcoin-update.log`
- **Propósito**: Atualiza dados dos últimos 7 dias

### 2. **Atualização Recente**
- **Comando**: `php artisan bitcoin:populate-historical-data --days=1 --force`
- **Frequência**: A cada 4 horas
- **Log**: `storage/logs/bitcoin-update-recent.log`
- **Propósito**: Mantém dados recentes atualizados

### 3. **População Histórica Semanal**
- **Comando**: `php artisan bitcoin:populate-historical-data --days=365 --force`
- **Frequência**: Semanalmente aos domingos às 02:00
- **Log**: `storage/logs/bitcoin-historical.log`
- **Propósito**: Garante dados históricos completos de 1 ano

## 🛠️ Configuração

### Cronjob do Sistema
A cronjob do sistema está configurada para executar o Laravel Scheduler a cada minuto:

```bash
* * * * * cd /home/marcos/Downloads/wallet-monitor && php artisan schedule:run >> /dev/null 2>&1
```

### Verificar Status
Use o script de verificação para monitorar as cronjobs:

```bash
./check-cron-status.sh
```

## 📊 Comandos Manuais

### Atualizar Dados Recentes
```bash
php artisan bitcoin:populate-historical-data --days=1 --force
```

### Atualizar Dados de 7 Dias
```bash
php artisan bitcoin:populate-historical-data --days=7 --force
```

### Popular Dados Históricos Completos
```bash
php artisan bitcoin:populate-historical-data --days=365 --force
```

### Listar Tarefas Agendadas
```bash
php artisan schedule:list
```

## 📈 Monitoramento

### Logs de Execução
- **Diário**: `storage/logs/bitcoin-update.log`
- **Recente**: `storage/logs/bitcoin-update-recent.log`
- **Histórico**: `storage/logs/bitcoin-historical.log`

### Verificar Dados na Base
```bash
php artisan tinker --execute="echo 'Registros diários: ' . App\Models\BitcoinPriceHistory::where('is_daily', true)->count();"
```

## 🔧 Solução de Problemas

### Se as Cronjobs Não Estiverem Funcionando

1. **Verificar se a cronjob do sistema está ativa**:
   ```bash
   crontab -l
   ```

2. **Reconfigurar a cronjob do sistema**:
   ```bash
   echo "* * * * * cd /home/marcos/Downloads/wallet-monitor && php artisan schedule:run >> /dev/null 2>&1" | crontab -
   ```

3. **Testar manualmente**:
   ```bash
   php artisan schedule:run
   ```

4. **Verificar logs do Laravel**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Rate Limiting
As cronjobs incluem proteção contra rate limiting da API CoinGecko:
- Aguarda entre execuções
- Usa cache para evitar chamadas desnecessárias
- Logs detalhados para monitoramento

## 📝 Notas Importantes

- **Dados Atuais**: 365 registros diários (1 ano completo)
- **Última Atualização**: Verificada automaticamente
- **Backup**: Dados históricos preservados
- **Performance**: Otimizado para evitar sobrecarga da API

## 🚀 Benefícios

- **Automatização**: Dados sempre atualizados
- **Confiabilidade**: Múltiplas fontes de atualização
- **Monitoramento**: Logs detalhados
- **Flexibilidade**: Comandos manuais disponíveis 