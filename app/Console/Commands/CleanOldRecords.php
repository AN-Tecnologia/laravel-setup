<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanOldRecords extends Command
{
    protected $signature = 'clean:old-records';

    protected $description = 'Clean up client_requests and server_responses older than 1 month';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $dateThreshold = now()->subMonths(1);

        DB::table('client_requests')->where('created_at', '<', $dateThreshold)->delete();
        DB::table('server_responses')->where('created_at', '<', $dateThreshold)->delete();
        DB::table('api_requests')->where('created_at', '<', $dateThreshold)->delete();
        DB::table('api_responses')->where('created_at', '<', $dateThreshold)->delete();

        $this->info('Old records cleaned successfully!');
    }
}
