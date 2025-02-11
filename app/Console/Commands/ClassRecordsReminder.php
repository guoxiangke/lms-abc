<?php

namespace App\Console\Commands;

use App\Models\ClassRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ClassRecordsReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'classrecords:reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reminder teacher/admin class is begining';

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
        $now = Carbon::now();
        if ($now->minute > 30) {
            $now->startOfHour()->addHour();
        } else {
            $now->minute = 30;
            $now->second = 0;
        }
        ClassRecord::where('generated_at', $now)->each(function (ClassRecord $classRecord) {
            // 通知老师

            // 通知管理员
            $teacher = $classRecord->teacher->profiles->first()->name;
            $student = $classRecord->user->profiles->first()->name;
            $date = $classRecord->generated_at->format('H:i 周N');
            $title = "$student 将在 $date 上课, 老师：$teacher";
            $zoom = 'https://zoom.us/j/'.$classRecord->teacher->teacher->pmi;
            bark_notify($title.$classRecord->order->title, $zoom, false, 'ipad');
        });
    }
}
