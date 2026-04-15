<?php

use App\Models\Assignment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Trainer Dashboard')] class extends Component {

    public function render()
    {
        $trainer = auth()->user();

        $clients       = $trainer->clients()->latest()->get();
        $totalPlans    = $trainer->workoutPlans()->count();
        $totalExercises = $trainer->exercises()->count();

        // Upcoming assignments (next 7 days)
        $upcoming = Assignment::with(['client', 'workoutPlan'])
            ->where('trainer_id', $trainer->id)
            ->whereBetween('scheduled_date', [today(), today()->addDays(7)])
            ->orderBy('scheduled_date')
            ->get();

        return view('pages.trainer.dashboard', compact('clients', 'totalPlans', 'totalExercises', 'upcoming'));
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

        <flux:heading size="xl">{{ __('Welcome back, :name!', ['name' => auth()->user()->name]) }}</flux:heading>

        {{-- Stats --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <flux:card class="flex flex-col gap-1">
                <flux:text class="text-sm text-zinc-500">{{ __('Clients') }}</flux:text>
                <flux:heading size="2xl">{{ $clients->count() }}</flux:heading>
            </flux:card>
            <flux:card class="flex flex-col gap-1">
                <flux:text class="text-sm text-zinc-500">{{ __('Workout Plans') }}</flux:text>
                <flux:heading size="2xl">{{ $totalPlans }}</flux:heading>
            </flux:card>
            <flux:card class="flex flex-col gap-1">
                <flux:text class="text-sm text-zinc-500">{{ __('Exercises') }}</flux:text>
                <flux:heading size="2xl">{{ $totalExercises }}</flux:heading>
            </flux:card>
        </div>

        {{-- Upcoming Assignments --}}
        <div class="flex flex-col gap-3">
            <flux:heading size="lg">{{ __('Next 7 Days') }}</flux:heading>

            @forelse ($upcoming as $a)
                @php $pct = $a->completionPercent(); @endphp
                <div class="flex items-center gap-4 rounded-lg border border-zinc-200 dark:border-zinc-700 px-4 py-3">
                    <div class="flex-1 min-w-0">
                        <div class="font-medium">{{ $a->client->name }}</div>
                        <div class="text-sm text-zinc-500">
                            {{ $a->workoutPlan->name }} · {{ $a->scheduled_date->format('M j') }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-24 h-2 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                            <div class="h-full rounded-full {{ $pct === 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                                style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="text-sm font-medium {{ $pct === 100 ? 'text-green-600' : 'text-zinc-500' }}">
                            {{ $pct }}%
                        </span>
                    </div>
                </div>
            @empty
                <flux:text class="text-zinc-500">{{ __('No workouts assigned for the next 7 days.') }}</flux:text>
            @endforelse
        </div>

        {{-- Client list --}}
        @if ($clients->isNotEmpty())
            <div class="flex flex-col gap-3">
                <flux:heading size="lg">{{ __('Clients') }}</flux:heading>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($clients as $client)
                        <a href="{{ route('trainer.client-detail', $client->id) }}"
                           class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                            <flux:avatar :name="$client->name" :initials="$client->initials()" />
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ $client->name }}</div>
                                <div class="text-sm text-zinc-500">
                                    {{ $client->assignments()->count() }} {{ __('workouts') }}
                                </div>
                            </div>
                            <flux:icon name="chevron-right" class="size-4 text-zinc-400 shrink-0" />
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
</div>
