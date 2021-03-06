<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Storage;

class DemoCron extends Command
{
    // php artisan make:command DemoCron --command=demo:cron

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
            Log::info("Cron Job Image Delete running at : ". now());
            Log::info('이미지 삭제 로직 시작'.now());
            $imageList = DB::table('tr_image')->where('status', 'f')->get();

            if($imageList) {
                $disk = Storage::build([
                    'driver' => 'local',
                    'root' => 'images',
                ]);
                foreach ($imageList as $item) {
//                $disk->move($item->image_name, 이동할 경로)
                    if($disk->exists($item->image_name)) {
                        $disk->delete($item->image_name);
                        DB::table('tr_image')->where('no', $item->no)->delete();
                    } else {
                        Log::warning('이미지 경로 못찾음 : '.$item->image_name);
                    }
                }
            } else {
                Log::info('삭제할 이미지 없음.');
            }
            Log::info('Cron Job Image Delete running stop at : '.now());

        } catch (Exception $e) {
            Log::emergency($e->getMessage());
        }
    }
}
