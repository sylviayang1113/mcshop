<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        DB::listen(function (QueryExecuted $event) {
            $sql = $event->sql;
            $bindings = $event->bindings;
            $time = $event->time;

            $bindings = array_map(function ($binding) {
                if (is_string($binding)) {
                    return "'$binding'";
                } else if ($binding instanceof \DateTime) {
                    return $binding->format("'Y-m-d H:i:s'");
                }
                return $binding;
            }, $bindings);

            $sql = str_replace('?', '%s', $sql);
            $sql = sprintf($sql, ...$bindings);
            // $sql = vsprintf($sql, $bindings);
        });
    }
}
