<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;

class ResolveTenantContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // seller user -> tenant_id from user
        if ($user && $user->user_type === 'tenant_user') {
            TenantContext::set($user->tenant_id);
        } else {
            // platform panel (or guests) -> no tenant context
            TenantContext::clear();
        }

        return $next($request);
    }
}
