<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Role selector -->
            <div class="flex flex-col gap-2">
                <flux:label>{{ __('I am a…') }}</flux:label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="role" value="trainer"
                               class="peer sr-only"
                               {{ old('role', 'trainer') === 'trainer' ? 'checked' : '' }}>
                        <div class="flex flex-col items-center gap-2 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 px-4 py-4 text-center transition
                                    peer-checked:border-blue-500 peer-checked:bg-blue-500/5 hover:border-zinc-400 dark:hover:border-zinc-500">
                            <svg class="size-7 text-zinc-400 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <div>
                                <div class="font-semibold text-sm text-zinc-800 dark:text-zinc-100">{{ __('Trainer') }}</div>
                                <div class="text-xs text-zinc-500 mt-0.5">{{ __('I coach clients') }}</div>
                            </div>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="role" value="client"
                               class="peer sr-only"
                               {{ old('role') === 'client' ? 'checked' : '' }}>
                        <div class="flex flex-col items-center gap-2 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 px-4 py-4 text-center transition
                                    peer-checked:border-blue-500 peer-checked:bg-blue-500/5 hover:border-zinc-400 dark:hover:border-zinc-500">
                            <svg class="size-7 text-zinc-400 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                            </svg>
                            <div>
                                <div class="font-semibold text-sm text-zinc-800 dark:text-zinc-100">{{ __('Client') }}</div>
                                <div class="text-xs text-zinc-500 mt-0.5">{{ __('I follow a plan') }}</div>
                            </div>
                        </div>
                    </label>
                </div>
                @error('role')
                    <flux:error>{{ $message }}</flux:error>
                @enderror
            </div>

            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                {{ __('Create account') }}
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
