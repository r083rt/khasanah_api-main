<?php

namespace App\Console\Commands\Backup;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup DB';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $fileName =  date('Y-m-d') . '.sql';
            if (config('app.env') != 'local') {
                $this->backupDb($fileName);
            } else {
                $this->error('Failed: You"r Running in ' . config('app.env'));
            }
        } catch (\Throwable $th) {
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
        }
    }

    /**
     * Get Path
     */
    private function path($fileName)
    {
        return 'backup_db/' . $fileName;
    }

    /**
     * Backup DB
     */
    private function backupDb($fileName)
    {
        $dbHost = config('database.connections.mysql.read.host');
        $dbUsername = config('database.connections.mysql.username');
        $dbPassword = config('database.connections.mysql.password');
        $dbPort = config('database.connections.mysql.port');
        $dbDatabase = config('database.connections.mysql.database');
        $fileName = storage_path('app/' . $this->path($fileName));

        exec("mysqldump -h $dbHost -u $dbUsername -p'$dbPassword' -P $dbPort $dbDatabase > $fileName");
        $this->info('Success Dump File');
    }
}
