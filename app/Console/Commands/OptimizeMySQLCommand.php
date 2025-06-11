<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OptimizeMySQLCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mysql:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otimiza configurações do MySQL para melhor performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Otimizando configurações do MySQL...');

        try {
            // Aumentar sort_buffer_size para 128MB
            DB::statement('SET GLOBAL sort_buffer_size = 134217728');
            $this->info('✅ sort_buffer_size aumentado para 128MB');

            // Aumentar join_buffer_size para 64MB
            DB::statement('SET GLOBAL join_buffer_size = 67108864');
            $this->info('✅ join_buffer_size aumentado para 64MB');

            // Aumentar read_buffer_size para 32MB
            DB::statement('SET GLOBAL read_buffer_size = 33554432');
            $this->info('✅ read_buffer_size aumentado para 32MB');

            // Verificar configurações atuais
            $sortBuffer = DB::select("SHOW VARIABLES LIKE 'sort_buffer_size'")[0]->Value;
            $joinBuffer = DB::select("SHOW VARIABLES LIKE 'join_buffer_size'")[0]->Value;
            $readBuffer = DB::select("SHOW VARIABLES LIKE 'read_buffer_size'")[0]->Value;

            $this->info('');
            $this->info('Configurações atuais:');
            $this->info("sort_buffer_size: " . $this->formatBytes($sortBuffer));
            $this->info("join_buffer_size: " . $this->formatBytes($joinBuffer));
            $this->info("read_buffer_size: " . $this->formatBytes($readBuffer));

            $this->info('');
            $this->info('🎉 Otimização concluída com sucesso!');
            $this->warn('⚠️  Estas configurações são temporárias e serão perdidas após o restart do MySQL.');
            $this->warn('   Para configuração permanente, edite o arquivo my.cnf do MySQL.');

        } catch (\Exception $e) {
            $this->error('❌ Erro ao otimizar MySQL: ' . $e->getMessage());
            $this->error('   Certifique-se de que o usuário do banco tem privilégios SUPER.');
            return 1;
        }

        return 0;
    }

    /**
     * Formata bytes para formato legível
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
