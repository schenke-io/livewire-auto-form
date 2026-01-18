<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Workbench') }}</title>

    <!-- Compiled TailwindCSS & Vite -->
    @vite(['workbench/resources/js/workbench.js', 'workbench/resources/css/workbench.css'])

    <!-- FluxUI styling system -->
    @fluxAppearance


    <style>
        /* Small helpers that play nicely with FluxUI */
        :root {
            --color-accent: #06b6d4; /* cyan-500 */
            --color-accent-foreground: #ffffff;
            --fx-bg: 247 247 248; /* light surface */
            --fx-fg: 17 24 39;     /* text */
        }
    </style>
<body class="h-full bg-[rgb(var(--fx-bg))] text-[rgb(var(--fx-fg))]">
<div class="min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-64 shrink-0 border-r border-gray-200 bg-white/80 backdrop-blur supports-[backdrop-filter]:bg-white/60 flex flex-col">
        <div class="h-16 flex items-center gap-3 px-4 border-b border-gray-200">
            <span class="inline-flex size-9 items-center justify-center rounded-md bg-accent text-accent-foreground shadow-sm">
                <!-- FluxUI icon example -->
                <flux:icon icon="sparkles" variant="mini" />
            </span>
            <div class="leading-tight">
                <div class="text-sm text-gray-500">Workbench</div>
                <div class="font-semibold">Livewire Auto Form</div>
            </div>
        </div>
        <nav class="py-4 flex-1" id="sidebar-nav">
            <flux:navlist>
                <flux:navlist.item href="{{ url('/') }}" :current="request()->is('/')">
                    Dashboard
                </flux:navlist.item>
                <flux:navlist.item href="{{ route('wizard') }}" :current="request()->routeIs('wizard')">
                    User Wizard
                </flux:navlist.item>
                <flux:navlist.item href="{{ route('cities.index') }}" :current="request()->routeIs('cities.index')">
                    Cities
                </flux:navlist.item>
                <flux:navlist.item href="{{ route('countries.index') }}" :current="request()->routeIs('countries.index')">
                    Countries
                </flux:navlist.item>
            </flux:navlist>
        </nav>

        <div class="p-4 border-t border-gray-200">
            <form action="{{ route('terminate') }}" method="POST" onsubmit="return confirm('Terminate the server?')">
                @csrf
                <flux:button type="submit" variant="danger" icon="stop-circle" class="w-full">
                    Terminate Server
                </flux:button>
            </form>
        </div>
    </aside>

    <!-- Main content area -->
    <div class="flex-1 min-w-0">
        <!-- Top bar (optional) -->
        <header class="h-16 flex items-center justify-between gap-3 px-6 border-b border-gray-200 bg-white">
            <flux:heading size="lg" class="text-gray-900">{{ $title ?? 'Home' }}</flux:heading>
        </header>

        <main class="p-6">
            @isset($slot)
                {{ $slot }}
            @else
                @yield('content')
            @endisset
        </main>
    </div>
</div>

<!-- Global flux-style popup for edit/save notifications -->
<div x-data="{
        show:false,
        msg:'',
        color:'yellow',
        timer:null,
        open(message, tone){
            this.msg = message;
            this.color = tone;
            this.show = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(()=> this.show=false, 1600);
        }
    }"
     x-on:field-updated.window="open('Field updated', 'yellow')"
     x-on:saved.window="open('Saved successfully', 'green')"
     class="fixed bottom-4 right-4 z-50">
    <div x-show="show" x-transition
         :class="{
            'bg-yellow-100 text-yellow-900 ring-1 ring-yellow-300': color==='yellow',
            'bg-green-100 text-green-900 ring-1 ring-green-300': color==='green'
         }"
         class="px-3 py-2 rounded-md shadow-lg flex items-center gap-2 min-w-44">
        <template x-if="color==='yellow'">
            <flux:icon icon="sparkles" variant="mini" />
        </template>
        <template x-if="color==='green'">
            <flux:icon icon="check" variant="mini" />
        </template>
        <span class="text-sm" x-text="msg"></span>
    </div>
</div>

<!-- Fallback note if FluxUI assets fail to load -->
<noscript>
    <div class="fixed inset-x-0 bottom-0 m-4 rounded-md bg-yellow-100 p-3 text-sm text-yellow-900 shadow">
        JavaScript is disabled; FluxUI icons may not render.
    </div>
</noscript>
    @fluxScripts
</body>
</html>
