<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\TenantContext;
use App\Services\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\View\Factory as ViewFactory;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        protected TenantResolver $resolver,
        protected TenantContext $tenantContext,
        protected ViewFactory $view,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $parameter = config('tenancy.route_parameter', 'tenant_slug');
        $slug = $request->route($parameter);

        abort_unless(is_string($slug) && $slug !== '', 404);

        $tenant = $this->resolver->resolve($slug);

        $this->tenantContext->set($tenant);
        $this->view->share('currentTenant', $tenant);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
