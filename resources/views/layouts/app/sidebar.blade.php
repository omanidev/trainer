<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        @include('partials.head')
        <script>
            // Theme management - must run before page renders to prevent flash
            (function() {
                const theme = localStorage.getItem('theme') || 'dark';
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                @auth
                    @if (auth()->user()->isTrainer())
                        <flux:sidebar.group :heading="__('Trainer nav')" class="grid">
                            <flux:sidebar.item icon="home" :href="route('trainer.dashboard')" :current="request()->routeIs('trainer.dashboard')" wire:navigate>
                                {{ __('Dashboard') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="calendar" :href="route('trainer.calendar')" :current="request()->routeIs('trainer.calendar')" wire:navigate>
                                {{ __('Calendar') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="users" :href="route('trainer.clients')" :current="request()->routeIs('trainer.clients')" wire:navigate>
                                {{ __('Clients') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="bolt" :href="route('trainer.exercises')" :current="request()->routeIs('trainer.exercises')" wire:navigate>
                                {{ __('Exercises') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="clipboard-document-list" :href="route('trainer.plans')" :current="request()->routeIs('trainer.plans')" wire:navigate>
                                {{ __('Workout Plans') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="calendar-days" :href="route('trainer.assign')" :current="request()->routeIs('trainer.assign')" wire:navigate>
                                {{ __('Assign') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>
                    @else
                        <flux:sidebar.group :heading="__('My Training')" class="grid">
                            <flux:sidebar.item icon="home" :href="route('client.dashboard')" :current="request()->routeIs('client.dashboard')" wire:navigate>
                                {{ __("Today's Workout") }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="chart-bar" :href="route('client.progress')" :current="request()->routeIs('client.progress')" wire:navigate>
                                {{ __('Progress') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="clock" :href="route('client.history')" :current="request()->routeIs('client.history')" wire:navigate>
                                {{ __('History') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>
                    @endif
                @endauth
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                {{ __('Settings') }}
                        </flux:menu.item>
                        <flux:menu.item
                            as="button"
                            type="button"
                            icon="sun"
                            class="w-full cursor-pointer dark:hidden"
                            onclick="localStorage.setItem('theme', 'dark'); document.documentElement.classList.add('dark'); window.location.reload();"
                        >
                            {{ __('Dark Mode') }}
                        </flux:menu.item>
                        <flux:menu.item
                            as="button"
                            type="button"
                            icon="moon"
                            class="w-full cursor-pointer hidden dark:block"
                            onclick="localStorage.setItem('theme', 'light'); document.documentElement.classList.remove('dark'); window.location.reload();"
                        >
                            {{ __('Light Mode') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
