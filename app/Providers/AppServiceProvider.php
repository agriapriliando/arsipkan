<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\File;
use App\Models\Tag;
use App\Models\UploadLink;
use App\Models\UserAccount;
use App\Policies\CategoryPolicy;
use App\Policies\FilePolicy;
use App\Policies\TagPolicy;
use App\Policies\UploadLinkPolicy;
use App\Policies\UserAccountPolicy;
use App\Services\Tenancy\TenantContext;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, fn (): TenantContext => new TenantContext);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->configureDefaults();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(File::class, FilePolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(UploadLink::class, UploadLinkPolicy::class);
        Gate::policy(UserAccount::class, UserAccountPolicy::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        app()->setLocale(config('app.locale'));
        Carbon::setLocale(config('app.locale'));
        CarbonImmutable::setLocale(config('app.locale'));
        Date::use(CarbonImmutable::class);
        Paginator::useBootstrapFive();

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
