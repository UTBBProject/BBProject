<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Cron_scripts as CronScripts;
class CronJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:earningsLog {--dFrom=*} {--dTo=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return mixed
     */
    public function handle()
    {
        $arguments = $this->options();
        $runCron = new CronScripts($arguments);
        $runCron->earningsLog();
        //$this->info('hello world.');
    }
}