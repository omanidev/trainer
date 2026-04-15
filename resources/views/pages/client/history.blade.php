<?php

use App\Models\Assignment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('My Progress')] class extends Component {
    use WithPagination;

    public function render()
    {
        $assignments = Assignment::with(['workoutPlan', 'exerciseLogs'])
            ->where('client_id', auth()->id())
            ->where('scheduled_date', '<', today())
            ->orderByDesc('scheduled_date')
            ->paginate(15);

        return view('pages.client.history', compact('assignments'));
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

        <flux:heading size="xl">{{ __('My Progress') }}</flux:heading>

        @forelse ($assignments as $a)
            @php
                $total = $a->workoutPlan->workoutPlanExercises()->count();
                $done  = $a->exerciseLogs->filter(fn($l) => $l->completed_at)->count();
                $pct   = $total > 0 ? (int)round($done / $total * 100) : 0;
            @endphp

            <div class="flex items-center gap-4 rounded-lg border border-zinc-200 dark:border-zinc-700 px-4 py-3">
                <div class="flex-1 min-w-0">
                    <div class="font-medium truncate">{{ $a->workoutPlan->name }}</div>
                    <div class="text-sm text-zinc-500">{{ $a->scheduled_date->format('l, M j, Y') }}</div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <div class="w-24 h-2 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                        <div class="h-full rounded-full {{ $pct === 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                            style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="text-sm font-semibold w-10 text-right {{ $pct === 100 ? 'text-green-600' : 'text-zinc-500' }}">
                        {{ $pct }}%
                    </span>
                    @if ($pct === 100)
                        <flux:badge color="green">{{ __('Done') }}</flux:badge>
                    @else
                        <flux:badge color="zinc">{{ $done }}/{{ $total }}</flux:badge>
                    @endif
                </div>
            </div>
        @empty
            <flux:card class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                <flux:icon name="chart-bar" class="size-12 text-zinc-400" />
                <flux:heading>{{ __('No history yet') }}</flux:heading>
                <flux:text>{{ __('Complete your first workout to see your progress here.') }}</flux:text>
            </flux:card>
        @endforelse

        {{ $assignments->links() }}
</div>
