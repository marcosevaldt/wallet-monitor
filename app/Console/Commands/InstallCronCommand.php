<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallCronCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:install {--force : Forçar instalação mesmo se já existir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instala o Laravel Scheduler no crontab do sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');

        $this->info("🕐 Instalando Laravel Scheduler no crontab...");

        // Verificar se já está instalado
        if ($this->isAlreadyInstalled() && !$force) {
            $this->warn("⚠️  Laravel Scheduler já está configurado no crontab!");
            $this->info("💡 Use --force para reinstalar");
            return 0;
        }

        // Criar a entrada do crontab
        $cronEntry = "* * * * * cd " . base_path() . " && php artisan schedule:run >> /dev/null 2>&1";
        
        try {
            if ($force) {
                // Forçar instalação - substituir crontab existente
                $this->installCronEntry($cronEntry, true);
            } else {
                // Instalação normal - adicionar ao crontab existente
                $this->installCronEntry($cronEntry, false);
            }

            $this->info("✅ Laravel Scheduler instalado com sucesso!");
            $this->info("📋 Para verificar: crontab -l");
            $this->info("📋 Para testar: php artisan schedule:run");
            $this->info("📋 Para remover: crontab -r");
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erro ao instalar crontab: " . $e->getMessage());
            $this->info("💡 Execute manualmente: crontab -e");
            $this->info("💡 Adicione: {$cronEntry}");
            return 1;
        }
    }

    /**
     * Verifica se o Laravel Scheduler já está instalado
     */
    protected function isAlreadyInstalled(): bool
    {
        $output = shell_exec('crontab -l 2>/dev/null');
        
        if (!$output) {
            return false;
        }

        $schedulerPath = base_path() . " && php artisan schedule:run";
        return str_contains($output, $schedulerPath);
    }

    /**
     * Instala a entrada do crontab
     */
    protected function installCronEntry(string $cronEntry, bool $force = false): void
    {
        if ($force) {
            // Substituir crontab existente
            $command = "echo '{$cronEntry}' | crontab -";
        } else {
            // Adicionar ao crontab existente
            $existingCron = shell_exec('crontab -l 2>/dev/null') ?: '';
            $newCron = $existingCron . PHP_EOL . $cronEntry;
            $command = "echo '{$newCron}' | crontab -";
        }

        $result = shell_exec($command);
        
        if ($result !== null) {
            throw new \Exception("Falha ao executar comando: {$command}");
        }
    }
}
