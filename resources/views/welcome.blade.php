<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TrainTrack — Personal Training, Simplified</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @if(app()->getLocale() === 'ar')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        html[dir="rtl"] body { font-family: 'Cairo', sans-serif !important; }
    </style>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Apply theme from localStorage
        function applyTheme() {
            const theme = localStorage.getItem('theme') || 'dark';
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }

        // Initialize theme immediately to prevent flash
        applyTheme();

        // Re-apply theme on Livewire navigation (SPA-style page changes)
        document.addEventListener('livewire:navigated', function() {
            applyTheme();
        });

        // Global theme toggle function
        window.toggleTheme = function() {
            const currentTheme = localStorage.getItem('theme') || 'dark';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            localStorage.setItem('theme', newTheme);

            if (newTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            // Dispatch custom event for any listeners
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: newTheme } }));
        };
    </script>
</head>
<body class="bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased" style="font-family: 'Instrument Sans', sans-serif;">

    {{-- NAV --}}
    <header class="fixed inset-x-0 top-0 z-50 border-b border-zinc-200/60 dark:border-zinc-800/60 bg-white/80 dark:bg-zinc-950/80 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="/" class="flex items-center gap-2">
                <div class="flex size-8 items-center justify-center rounded-lg bg-blue-600">
                    <svg class="size-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
                <span class="text-lg font-semibold tracking-tight">TrainTrack</span>
            </a>
            <nav class="flex items-center gap-3">
                <!-- Theme Toggle -->
                <button
                    type="button"
                    class="flex items-center justify-center size-9 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition hover:bg-zinc-200 dark:hover:bg-zinc-700 dark:hidden"
                    onclick="window.toggleTheme();"
                    aria-label="Switch to dark mode"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                </button>
                <button
                    type="button"
                    class="hidden dark:flex items-center justify-center size-9 rounded-lg border border-zinc-700 bg-zinc-800 text-zinc-300 transition hover:bg-zinc-700"
                    onclick="window.toggleTheme();"
                    aria-label="Switch to light mode"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>
                </button>

                @auth
                    <a href="{{ route('dashboard') }}"
                       class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-500">
                        {{ __('Go to App') }}
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="rounded-lg border-2 border-zinc-900 dark:border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-900 dark:text-zinc-300 transition hover:bg-zinc-900 hover:text-white dark:hover:bg-zinc-300 dark:hover:text-zinc-900">
                        {{ __('Log in') }}
                    </a>
                    <a href="{{ route('register') }}"
                       class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-500">
                        {{ __('Get Started') }}
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main>

        {{-- HERO --}}
        <section class="relative overflow-hidden px-6 pb-24 pt-40">
            <div class="pointer-events-none absolute inset-0 flex items-start justify-center">
                <div class="h-150 w-225 rounded-full bg-blue-600/10 blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-4xl text-center">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-blue-500/30 bg-blue-500/10 px-4 py-1.5 text-sm font-medium text-blue-400">
                    <span class="size-1.5 rounded-full bg-blue-400"></span>
                    {{ __('Built for real trainers & motivated clients') }}
                </div>

                <h1 class="mb-6 text-5xl font-bold leading-tight tracking-tight text-zinc-900 dark:text-white sm:text-6xl lg:text-7xl">
                    {{ __('Train smarter.') }}<br>
                    <span class="bg-linear-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">{{ __('Track everything.') }}</span>
                </h1>

                <p class="mx-auto mb-10 max-w-2xl text-lg leading-relaxed text-zinc-600 dark:text-zinc-400">
                    {{ __('TrainTrack connects personal trainers with their clients — assign workouts, monitor progress, and keep every session accountable, all in one place.') }}
                </p>

                <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="rounded-xl bg-blue-600 px-8 py-3.5 text-base font-semibold text-white shadow-lg shadow-blue-900/40 transition hover:bg-blue-500">
                            {{ __('Open Dashboard →') }}
                        </a>
                    @else
                        <a href="{{ route('register') }}"
                           class="rounded-xl bg-blue-600 px-8 py-3.5 text-base font-semibold text-white shadow-lg shadow-blue-900/40 transition hover:bg-blue-500">
                            {{ __('Start for free') }}
                        </a>
                        <a href="{{ route('login') }}"
                           class="rounded-xl border-2 border-zinc-900 dark:border-zinc-700 px-8 py-3.5 text-base font-semibold text-zinc-900 dark:text-zinc-300 transition hover:bg-zinc-900 hover:text-white dark:hover:border-zinc-500 dark:hover:text-white">
                            {{ __('Log in') }}
                        </a>
                    @endauth
                </div>
            </div>

            {{-- Mock UI Preview --}}
            <div class="relative mx-auto mt-20 max-w-4xl">
                <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-1 shadow-2xl shadow-black/60">
                    <div class="flex items-center gap-1.5 border-b border-zinc-800 px-4 py-3">
                        <div class="size-3 rounded-full bg-zinc-700"></div>
                        <div class="size-3 rounded-full bg-zinc-700"></div>
                        <div class="size-3 rounded-full bg-zinc-700"></div>
                        <div class="mx-auto text-xs text-zinc-600">traintrack.app/client/dashboard</div>
                    </div>

                    <div class="p-6">
                        <div class="mb-5 text-lg font-semibold text-zinc-200">Today — Monday, Apr 14</div>

                        <div class="rounded-xl border border-zinc-800 bg-zinc-800/50 p-5">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <div class="font-semibold text-zinc-100">Upper Body Strength</div>
                                    <div class="mt-0.5 text-sm text-zinc-500">Push hard today!</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-blue-400">2/4</div>
                                </div>
                            </div>
                            <div class="mb-4 h-2 overflow-hidden rounded-full bg-zinc-700">
                                <div class="h-full w-1/2 rounded-full bg-blue-500"></div>
                            </div>
                            <div class="flex flex-col gap-2">
                                @foreach ([['Push-ups','Chest','3×12',true],['Pull-ups','Back','3×12',true],['Shoulder Press','Shoulders','3×12',false],['Dumbbell Curl','Arms','3×12',false]] as [$name,$group,$detail,$done])
                                <div class="flex items-center gap-3 rounded-lg px-3 py-2.5
                                    {{ $done ? 'border border-green-800/40 bg-green-950/60' : 'border border-zinc-700/50' }}">
                                    <div class="flex size-5 shrink-0 items-center justify-center rounded-full
                                        {{ $done ? 'bg-green-500' : 'border-2 border-zinc-600' }}">
                                        @if($done)
                                        <svg class="size-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        @endif
                                    </div>
                                    <span class="flex-1 text-sm {{ $done ? 'text-zinc-500 line-through' : 'text-zinc-200' }}">{{ $name }}</span>
                                    <span class="text-xs text-zinc-500">{{ $detail }}</span>
                                    <span class="text-xs text-zinc-600">· {{ $group }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pointer-events-none absolute -bottom-10 inset-x-20 h-20 rounded-full bg-blue-600/20 blur-2xl"></div>
            </div>
        </section>

        {{-- HOW IT WORKS --}}
        <section class="border-t border-zinc-200 dark:border-zinc-800/60 px-6 py-24">
            <div class="mx-auto max-w-5xl">
                <div class="mb-16 text-center">
                    <div class="mb-3 text-sm font-semibold uppercase tracking-widest text-blue-500">{{ __('How it works') }}</div>
                    <h2 class="text-3xl font-bold text-zinc-900 dark:text-white sm:text-4xl">{{ __('Simple for trainers. Motivating for clients.') }}</h2>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    @foreach ([
                        ['01', __('Build your library'), __('Create your exercise library with muscle groups, sets, and reps. Then group them into reusable workout plans.'), 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'],
                        ['02', __('Assign to clients'), __('Pick a client, choose a plan, and schedule it on a date. Add a personal note. Done in seconds.'), 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
                        ['03', __('Track progress'), __('Clients check off each exercise as they go. You see completion percentages in real time on your dashboard.'), 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
                    ] as [$num, $title, $desc, $icon])
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center gap-4">
                            <div class="flex size-12 shrink-0 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-600/15">
                                <svg class="size-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                                </svg>
                            </div>
                            <span class="select-none text-4xl font-bold text-zinc-800">{{ $num }}</span>
                        </div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h3>
                        <p class="leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $desc }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- FEATURES SPLIT --}}
        <section class="border-t border-zinc-200 dark:border-zinc-800/60 px-6 py-24">
            <div class="mx-auto max-w-5xl">
                <div class="mb-16 text-center">
                    <div class="mb-3 text-sm font-semibold uppercase tracking-widest text-blue-500">{{ __('Features') }}</div>
                    <h2 class="text-3xl font-bold text-zinc-900 dark:text-white sm:text-4xl">{{ __('Everything you need, nothing you don\'t') }}</h2>
                </div>

                <div class="grid gap-6 md:grid-cols-2">

                    {{-- Trainer card --}}
                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 p-8">
                        <div class="mb-6 flex items-center gap-3">
                            <div class="flex size-10 items-center justify-center rounded-xl border border-violet-500/20 bg-violet-600/20">
                                <svg class="size-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wider text-violet-400">{{ __('For Trainers') }}</div>
                                <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Coach dashboard') }}</div>
                            </div>
                        </div>
                        <ul class="flex flex-col gap-3">
                            @foreach ([
                                __('Manage your full client roster'),
                                __('Build a reusable exercise library with muscle groups'),
                                __('Create named workout plans with sets & reps'),
                                __('Assign plans to any client on any date'),
                                __("See every client's completion percentage at a glance"),
                                __('7-day upcoming schedule on the dashboard'),
                            ] as $feature)
                            <li class="flex items-start gap-2.5 text-sm text-zinc-600 dark:text-zinc-400">
                                <svg class="mt-0.5 size-4 shrink-0 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Client card --}}
                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 p-8">
                        <div class="mb-6 flex items-center gap-3">
                            <div class="flex size-10 items-center justify-center rounded-xl border border-green-500/20 bg-green-600/20">
                                <svg class="size-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wider text-green-400">{{ __('For Clients') }}</div>
                                <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Training view') }}</div>
                            </div>
                        </div>
                        <ul class="flex flex-col gap-3">
                            @foreach ([
                                __("See today's assigned workout instantly"),
                                __('Tap to mark each exercise complete'),
                                __('Visual progress bar per session'),
                                __('Upcoming workouts for the week ahead'),
                                __('Full history of past sessions'),
                                __('Completion percentage for every workout'),
                            ] as $feature)
                            <li class="flex items-start gap-2.5 text-sm text-zinc-600 dark:text-zinc-400">
                                <svg class="mt-0.5 size-4 shrink-0 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- STATS --}}
        <section class="border-t border-zinc-200 dark:border-zinc-800/60 px-6 py-20">
            <div class="mx-auto max-w-4xl">
                <div class="grid grid-cols-2 gap-8 text-center md:grid-cols-4">
                    @foreach ([
                        ['100%', __('Free to use')],
                        ['∞', __('Clients & plans')],
                        [__('Real-time'), __('Progress tracking')],
                        ['0', __('Setup hassle')],
                    ] as [$stat, $label])
                    <div>
                        <div class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $stat }}</div>
                        <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-500">{{ $label }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="border-t border-zinc-200 dark:border-zinc-800/60 px-6 py-24">
            <div class="mx-auto max-w-2xl text-center">
                <div class="mb-10 inline-flex items-center justify-center rounded-full border border-blue-500/20 bg-blue-600/10 p-4">
                    <svg class="size-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                    </svg>
                </div>
                <h2 class="mb-4 text-4xl font-bold text-zinc-900 dark:text-white">{{ __('Ready to train better?') }}</h2>
                <p class="mb-10 text-lg text-zinc-600 dark:text-zinc-400">
                    {{ __("Join TrainTrack and bring structure to every session — whether you're the coach or the athlete.") }}
                </p>
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-block rounded-xl bg-blue-600 px-10 py-4 text-base font-semibold text-white shadow-lg shadow-blue-900/40 transition hover:bg-blue-500">
                        {{ __('Go to your dashboard →') }}
                    </a>
                @else
                    <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <a href="{{ route('register') }}"
                           class="rounded-xl bg-blue-600 px-10 py-4 text-base font-semibold text-white shadow-lg shadow-blue-900/40 transition hover:bg-blue-500">
                            {{ __('Create free account') }}
                        </a>
                        <a href="{{ route('login') }}"
                           class="rounded-xl border-2 border-zinc-900 dark:border-zinc-700 px-10 py-4 text-base font-semibold text-zinc-900 dark:text-zinc-300 transition hover:bg-zinc-900 hover:text-white dark:hover:border-zinc-500 dark:hover:text-white">
                            {{ __('Log in') }}
                        </a>
                    </div>
                @endauth
            </div>
        </section>

    </main>

    {{-- FOOTER --}}
    <footer class="border-t border-zinc-200 dark:border-zinc-800/60 px-6 py-8">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 text-sm text-zinc-500 dark:text-zinc-600 sm:flex-row">
            <div class="flex items-center gap-2">
                <div class="flex size-5 items-center justify-center rounded bg-blue-600">
                    <svg class="size-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                    </svg>
                </div>
                <span class="font-medium text-zinc-600 dark:text-zinc-500">TrainTrack</span>
            </div>
            <span>{{ __('Personal training, simplified.') }}</span>
        </div>
    </footer>

</body>
</html>
