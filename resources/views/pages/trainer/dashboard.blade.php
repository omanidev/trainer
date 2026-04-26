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
            <a href="{{ route('trainer.clients') }}" class="group">
                <flux:card class="flex flex-col gap-1 transition-all duration-200 hover:shadow-lg hover:scale-[1.02] hover:border-blue-300 dark:hover:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-950/30">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">{{ __('Clients') }}</flux:text>
                    <flux:heading size="2xl" class="dark:text-zinc-100">{{ $clients->count() }}</flux:heading>
                </flux:card>
            </a>
            <a href="{{ route('trainer.plans') }}" class="group">
                <flux:card class="flex flex-col gap-1 transition-all duration-200 hover:shadow-lg hover:scale-[1.02] hover:border-blue-300 dark:hover:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-950/30">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">{{ __('Workout Plans') }}</flux:text>
                    <flux:heading size="2xl" class="dark:text-zinc-100">{{ $totalPlans }}</flux:heading>
                </flux:card>
            </a>
            <a href="{{ route('trainer.exercises') }}" class="group">
                <flux:card class="flex flex-col gap-1 transition-all duration-200 hover:shadow-lg hover:scale-[1.02] hover:border-blue-300 dark:hover:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-950/30">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">{{ __('Exercises') }}</flux:text>
                    <flux:heading size="2xl" class="dark:text-zinc-100">{{ $totalExercises }}</flux:heading>
                </flux:card>
            </a>
        </div>

        {{-- Upcoming Assignments --}}
        <div class="flex flex-col gap-3">
            <flux:heading size="lg">{{ __('Next 7 Days') }}</flux:heading>

            @forelse ($upcoming as $a)
                @php $pct = $a->completionPercent(); @endphp
                <div class="flex items-center gap-4 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-3 hover:border-blue-300 dark:hover:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-950/30 transition-all duration-200">
                    <div class="flex-1 min-w-0">
                        <div class="font-medium dark:text-zinc-100">{{ $a->client->name }}</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $a->workoutPlan->name }} · {{ $a->scheduled_date->format('M j') }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-24 h-2 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                            <div class="h-full rounded-full {{ $pct === 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                                style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="text-sm font-medium {{ $pct === 100 ? 'text-green-600 dark:text-green-400' : 'text-zinc-500 dark:text-zinc-400' }}">
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
                           class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-3 hover:shadow-lg hover:scale-[1.02] hover:border-blue-300 dark:hover:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-950/30 transition-all duration-200">
                            <flux:avatar :name="$client->name" :initials="$client->initials()" />
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate dark:text-zinc-100">{{ $client->name }}</div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $client->assignments()->count() }} {{ __('workouts') }}
                                </div>
                            </div>
                            <flux:icon name="chevron-right" class="size-4 text-zinc-400 dark:text-zinc-500 shrink-0" />
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
</div>
