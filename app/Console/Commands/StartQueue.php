<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StartQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:start {--timeout=3600 : Maximum execution time in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Laravel queue worker for processing jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeout = $this->option('timeout');
        
        $this->info("🚀 Iniciando Laravel Queue Worker (timeout: {$timeout}s)");
        $this->info("📝 Para procesar jobs de citas C4C");
        $this->newLine();
        
        // Ejecutar el comando queue:work
        $this->call('queue:work', [
            '--timeout' => $timeout,
            '--tries' => 3,
            '--max-jobs' => 1000,
            '--max-time' => $timeout,
        ]);
    }
}
