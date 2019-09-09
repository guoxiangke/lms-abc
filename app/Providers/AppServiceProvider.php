<?php

namespace App\Providers;

use Carbon\Carbon;
use App\Models\Rrule;
use App\Models\Profile;
use App\Models\Student;
use App\Observers\RruleObserver;
use App\Observers\ProfileObserver;
use App\Observers\StudentObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // if ($this->app->environment() !== 'production') {
        //     $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        // }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Carbon::setLocale('zh');
        URL::forceScheme('https');

        // Flash::levels([
        //     'success' => 'alert-success',
        //     'warning' => 'alert-warning',
        //     'error' => 'alert-error',
        // ]);

        //observes
        Rrule::observe(RruleObserver::class);
        Student::observe(StudentObserver::class);
        Profile::observe(ProfileObserver::class);
    }
}
