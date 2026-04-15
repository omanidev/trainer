<?php

use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('My Clients')] class extends Component {

    // Create new client
    public string $name = '';
    public string $email = '';
    public string $password = '';

    // Link existing client
    public string $linkEmail = '';
    public ?User $foundUser = null;

    public function addClient(): void
    {
        $this->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        User::create([
            'name'       => $this->name,
            'email'      => $this->email,
            'password'   => $this->password,
            'role'       => 'client',
            'trainer_id' => auth()->id(),
        ]);

        $this->reset(['name', 'email', 'password']);
        Flux::toast(__('Client added successfully.'), variant: 'success');
    }

    public function searchClient(): void
    {
        $this->validate(['linkEmail' => 'required|email']);

        $user = User::where('email', $this->linkEmail)
            ->where('role', 'client')
            ->first();

        if (! $user) {
            $this->addError('linkEmail', __('No client account found with that email.'));
            $this->foundUser = null;
            return;
        }

        if ($user->trainer_id === auth()->id()) {
            $this->addError('linkEmail', __('This client is already in your roster.'));
            $this->foundUser = null;
            return;
        }

        if ($user->trainer_id !== null) {
            $this->addError('linkEmail', __('This client is already linked to another trainer.'));
            $this->foundUser = null;
            return;
        }

        $this->foundUser = $user;
    }

    public function linkClient(): void
    {
        if (! $this->foundUser) return;

        $this->foundUser->update(['trainer_id' => auth()->id()]);
        $this->reset(['linkEmail', 'foundUser']);
        Flux::toast(__('Client linked successfully.'), variant: 'success');
    }

    public function removeClient(int $id): void
    {
        $client = User::where('id', $id)->where('trainer_id', auth()->id())->firstOrFail();
        $client->update(['trainer_id' => null]);
        Flux::toast(__('Client removed.'), variant: 'success');
    }

    public function render()
    {
        return view('pages.trainer.clients', [
            'clients' => auth()->user()->clients()->latest()->get(),
        ]);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('My Clients') }}</flux:heading>
        <div class="flex gap-2">
            <flux:modal.trigger name="link-client">
                <flux:button variant="ghost" icon="link">{{ __('Link Existing') }}</flux:button>
            </flux:modal.trigger>
            <flux:modal.trigger name="add-client">
                <flux:button variant="primary" icon="plus">{{ __('Add Client') }}</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    {{-- Create new client modal --}}
    <flux:modal name="add-client" class="md:w-96">
        <div class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('Add New Client') }}</flux:heading>
            <flux:text class="text-zinc-500 text-sm">{{ __('Create a new account for your client. Share the credentials with them so they can log in.') }}</flux:text>

            <flux:input wire:model="name" :label="__('Full Name')" :placeholder="__('John Doe')" />
            <flux:input wire:model="email" :label="__('Email')" type="email" placeholder="client@example.com" />
            <flux:input wire:model="password" :label="__('Password')" type="password" :placeholder="__('Min 8 characters')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="addClient">
                    {{ __('Create & Add') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Link existing client modal --}}
    <flux:modal name="link-client" class="md:w-96">
        <div class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('Link Existing Client') }}</flux:heading>
            <flux:text class="text-zinc-500 text-sm">{{ __('If your client already has an account, find them by email and link them to your roster.') }}</flux:text>

            <div class="flex gap-2">
                <div class="flex-1">
                    <flux:input wire:model="linkEmail" :label="__('Client Email')" type="email" placeholder="client@example.com" />
                </div>
                <div class="self-end">
                    <flux:button wire:click="searchClient" icon="magnifying-glass">{{ __('Search') }}</flux:button>
                </div>
            </div>

            @if ($foundUser)
                <div class="flex items-center gap-3 rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/40 px-4 py-3">
                    <flux:avatar :name="$foundUser->name" :initials="$foundUser->initials()" />
                    <div class="flex-1 min-w-0">
                        <div class="font-medium truncate">{{ $foundUser->name }}</div>
                        <div class="text-sm text-zinc-500 truncate">{{ $foundUser->email }}</div>
                    </div>
                    <flux:badge color="green">{{ __('Found') }}</flux:badge>
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" wire:click="$set('foundUser', null)">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                @if ($foundUser)
                    <flux:button variant="primary" wire:click="linkClient" icon="link">
                        {{ __('Link Client') }}
                    </flux:button>
                @endif
            </div>
        </div>
    </flux:modal>

    @if ($clients->isEmpty())
        <flux:card class="flex flex-col items-center justify-center gap-3 py-16 text-center">
            <flux:icon name="users" class="size-12 text-zinc-400" />
            <flux:heading>{{ __('No clients yet') }}</flux:heading>
            <flux:text>{{ __('Create a new client account or link an existing one.') }}</flux:text>
        </flux:card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($clients as $client)
                @php
                    $total     = $client->assignments()->count();
                    $completed = $client->assignments()
                        ->where(function ($q) {
                            $q->whereHas('exerciseLogs', fn($l) => $l->whereNotNull('completed_at'));
                        })->count();
                @endphp

                <flux:card class="flex flex-col gap-3">
                    <div class="flex items-center gap-3">
                        <flux:avatar :name="$client->name" :initials="$client->initials()" />
                        <div class="flex-1 min-w-0">
                            <flux:heading class="truncate">{{ $client->name }}</flux:heading>
                            <flux:text class="truncate text-sm">{{ $client->email }}</flux:text>
                        </div>
                    </div>

                    <flux:text class="text-sm text-zinc-500">
                        {{ $total }} {{ $total === 1 ? __('assignment') : __('assignments') }}
                        @if ($total > 0)
                            · {{ $completed }} {{ __('completed') }}
                        @endif
                    </flux:text>

                    <div class="flex gap-2 mt-auto">
                        <flux:button size="sm" variant="ghost" icon="eye" tag="a"
                            :href="route('trainer.client-detail', $client->id)">
                            {{ __('View') }}
                        </flux:button>
                        <flux:button size="sm" variant="ghost" icon="trash"
                            wire:click="removeClient({{ $client->id }})"
                            :wire:confirm="__('Remove :name from your roster?', ['name' => $client->name])">
                        </flux:button>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif
</div>
