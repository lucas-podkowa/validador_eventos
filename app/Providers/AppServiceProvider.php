<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->getKey() ?: $request->ip());
        });

        RateLimiter::for('api-certificados', function (Request $request) {
            $key = $request->user()?->getKey() ?: $request->ip();

            return Limit::perMinute(60)->by('api-certificados:'.$key);
        });

        Activity::creating(function (Activity $activity): void {
            $user = auth()->user();
            $metadata = [];

            if ($user) {
                $metadata['causer_name'] = $user->name;
                $metadata['causer_email'] = $user->email;
            }

            if (! app()->runningInConsole()) {
                $request = request();
                $metadata['ip'] = $request->ip();
                $metadata['user_agent'] = mb_substr((string) $request->userAgent(), 0, 255);
                $metadata['method'] = $request->method();
                $metadata['path'] = '/'.ltrim($request->path(), '/');
            }

            $activity->properties = collect($activity->properties)->merge(
                array_filter($metadata, fn ($value) => $value !== null && $value !== '')
            );
        });
    }
}
