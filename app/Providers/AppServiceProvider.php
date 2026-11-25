<?php

namespace App\Providers;

use App\Models\AgendaItem;
use App\Models\CommunityPost;
use App\Models\CompetitionListing;
use App\Policies\PublicContentPolicy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Month and day names in Indonesian (translatedFormat, diffForHumans), the language of the whole UI.
        // Only dates: APP_LOCALE stays as it is for the rest of Laravel.
        Carbon::setLocale('id');

        foreach ([CompetitionListing::class, AgendaItem::class, CommunityPost::class] as $model) {
            Gate::policy($model, PublicContentPolicy::class);
        }
    }
}
