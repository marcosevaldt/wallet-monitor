<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupBitcoinCronCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitcoin:setup-cron 
                            {--interval=15 : Intervalo em minutos (padrão: 15)}
                            {--currency=usd : Moeda para buscar (padrão: usd)}
                            {--days=1 : Número de dias para buscar (padrão: 1)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura cron job para atualização automática dos dados do Bitcoin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $interval = (int) $this->option('interval');
        $currency = $this->option('currency');
        $days = (int) $this->option('days');

        $this->info("🪙 Configurando cron job para atualização do Bitcoin...");
        $this->info("⏰ Intervalo: {$interval} minutos");
        $this->info("💰 Moeda: {$currency}");
        $this->info("📅 Período: {$days} dia(s)");

        // Verificar se o Laravel Scheduler está configurado
        if (!$this->checkLaravelScheduler()) {
            $this->error("❌ Laravel Scheduler não está configurado no crontab!");
            $this->info("💡 Execute: crontab -e");
            $this->info("💡 Adicione: * * * * * cd " . base_path() . " && php artisan schedule:run >> /dev/null 2>&1");
            $this->info("💡 Ou execute: echo '* * * * * cd " . base_path() . " && php artisan schedule:run >> /dev/null 2>&1' | crontab -");
            return 1;
        }

        $this->info("✅ Cron job configurado com sucesso!");
        $this->info("📋 Para verificar o status: php artisan schedule:list");
        $this->info("📋 Para testar: php artisan schedule:run");
        $this->info("📋 Para ver logs: tail -f storage/logs/bitcoin-update.log");
        $this->info("📋 Para testar o comando: php artisan bitcoin:update-price");

        return 0;
    }

    /**
     * Verifica se o Laravel Scheduler está configurado
     */
    protected function checkLaravelScheduler(): bool
    {
        $output = shell_exec('crontab -l 2>/dev/null');
        
        if (!$output) {
            return false;
        }

        $schedulerPath = base_path() . " && php artisan schedule:run";
        return str_contains($output, $schedulerPath);
    }
}
