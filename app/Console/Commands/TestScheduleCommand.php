<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schedule;

class TestScheduleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:test {--run : Executar comandos agendados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa e executa comandos agendados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $run = $this->option('run');

        $this->info("🕐 Testando comandos agendados...");

        if ($run) {
            $this->info("▶️  Executando comandos agendados...");
            $this->call('schedule:run');
        } else {
            $this->info("📋 Listando comandos agendados:");
            $this->call('schedule:list');
            
            $this->info("");
            $this->info("💡 Para executar: php artisan schedule:test --run");
            $this->info("💡 Para ver logs: tail -f storage/logs/bitcoin-update.log");
        }

        return 0;
    }
}
