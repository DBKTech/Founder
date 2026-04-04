<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'PhytenHQ') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white">
    <div class="relative min-h-screen overflow-hidden">
        {{-- Background glow --}}
        <div class="absolute inset-0">
            <div class="absolute left-0 top-0 h-full w-1/2 bg-gradient-to-br from-sky-500/20 via-blue-500/10 to-transparent"></div>
            <div class="absolute right-0 top-0 h-full w-1/2 bg-gradient-to-bl from-emerald-500/20 via-green-500/10 to-transparent"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.08),transparent_40%)]"></div>
        </div>

        <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-10 lg:px-8">
            {{-- Header --}}
            <div class="mb-10 text-center">
                <p class="mb-3 inline-flex rounded-full border border-white/10 bg-white/5 px-4 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-white/70">
                    Unified Access Portal
                </p>

                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                    Welcome to
                    <span class="bg-gradient-to-r from-sky-300 via-white to-emerald-300 bg-clip-text text-transparent">
                       PhytenHQ
                    </span>
                </h1>

                <p class="mx-auto mt-5 max-w-2xl text-sm leading-7 text-white/70 sm:text-base">
                    Select the appropriate portal to continue. Use the HQ portal for administration and operations,
                    or the Seller portal for marketplace, customers, and sales activities.
                </p>
            </div>

            {{-- Portal cards --}}
            <div class="grid flex-1 gap-6 lg:grid-cols-2">
                {{-- HQ / Platform --}}
                <a href="{{ url('/platform') }}"
                   class="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur-md transition duration-300 hover:-translate-y-1 hover:border-sky-300/40 hover:bg-white/10">
                    <div class="absolute inset-0 bg-gradient-to-br from-sky-400/20 via-blue-500/10 to-transparent opacity-80"></div>

                    <div class="relative flex h-full flex-col">
                        <div class="mb-6 flex items-center justify-between">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-400/15 ring-1 ring-inset ring-sky-300/30">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-sky-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M6 18V9m4 9V5m4 13v-8m4 8v-4" />
                                </svg>
                            </div>

                            <span class="rounded-full border border-sky-300/20 bg-sky-300/10 px-3 py-1 text-xs font-medium text-sky-100">
                                HQ Access
                            </span>
                        </div>

                        <div class="mb-4">
                            <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                                HQ / Management Portal
                            </h2>
                            <p class="mt-3 max-w-xl text-sm leading-7 text-white/70 sm:text-base">
                                For administrators, finance teams, warehouse staff, and operational management.
                                Access products, orders, tenants, users, and system-wide controls.
                            </p>
                        </div>

                        <div class="mb-8 grid gap-3 text-sm text-white/75">
                            <div class="rounded-2xl border border-white/10 bg-black/10 px-4 py-3">Manage tenants, users, brands, and products</div>
                            <div class="rounded-2xl border border-white/10 bg-black/10 px-4 py-3">Monitor all orders, shipping, and payments</div>
                            <div class="rounded-2xl border border-white/10 bg-black/10 px-4 py-3">Oversee HQ operations and future reporting</div>
                        </div>

                        <div class="mt-auto flex items-center justify-between">
                            <span class="inline-flex items-center gap-2 rounded-2xl bg-sky-400 px-5 py-3 text-sm font-semibold text-slate-950 transition group-hover:bg-sky-300">
                                Enter HQ Portal
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </span>
                        </div>
                    </div>
                </a>

                {{-- Seller / App --}}
                <a href="{{ url('/app') }}"
                   class="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur-md transition duration-300 hover:-translate-y-1 hover:border-emerald-300/40 hover:bg-white/10">
                    <div class="absolute inset-0 bg-gradient-to-bl from-emerald-400/20 via-green-500/10 to-transparent opacity-80"></div>

                    <div class="relative flex h-full flex-col">
                        <div class="mb-6 flex items-center justify-between">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-400/15 ring-1 ring-inset ring-emerald-300/30">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 10H6L5 9z" />
                                </svg>
                            </div>

                            <span class="rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-xs font-medium text-emerald-100">
                                Seller Access
                            </span>
                        </div>

                        <div class="mb-4">
                            <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                                Seller / Sales Portal
                            </h2>
                            <p class="mt-3 max-w-xl text-sm leading-7 text-white/70 sm:text-base">
                                For master stokis, agents, fighters, and seller teams.
                                Access marketplace, customers, checkout, order history, and day-to-day sales tools.
                            </p>
                        </div>

                        <div class="mb-8 grid gap-3 text-sm text-white/75">
                            <div class="rounded-2xl border border-white/10 bg-black/10 px-4 py-3">Browse marketplace and create orders</div>
                            <div class="rounded-2xl border border-white/10 bg-black/10 px-4 py-3">Manage customers and checkout flows</div>
                            <div class="rounded-2xl border border-white/10 bg-black/10 px-4 py-3">Track sales activity and future commissions</div>
                        </div>

                        <div class="mt-auto flex items-center justify-between">
                            <span class="inline-flex items-center gap-2 rounded-2xl bg-emerald-400 px-5 py-3 text-sm font-semibold text-slate-950 transition group-hover:bg-emerald-300">
                                Enter Seller Portal
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </span>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Footer --}}
            <div class="mt-8 text-center text-sm text-white/50">
                Please select the portal that matches your role in the system.
            </div>
        </div>
    </div>
</body>
</html>