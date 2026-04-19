<?php

use App\Models\Assignment;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Client Detail')] class extends Component {

    public User $client;

    // Edit assignment state
    public ?int $editingAssignmentId = null;
    public string $editDate = '';
    public string $editNotes = '';

    // Which assignments are expanded to show exercises
    public array $expanded = [];

    // Control upcoming workouts display
    public bool $showAllUpcoming = false;
    public int $upcomingLimit = 3;

    // Control past workouts display
    public bool $showAllPast = false;
    public int $pastLimit = 5;

    public function mount(int $id): void
    {
        $this->client = User::where('id', $id)
            ->where('trainer_id', auth()->id())
            ->where('role', 'client')
            ->firstOrFail();
    }

    public function toggleExpand(int $assignmentId): void
    {
        if (in_array($assignmentId, $this->expanded)) {
            $this->expanded = array_values(array_filter($this->expanded, fn($i) => $i !== $assignmentId));
        } else {
            $this->expanded[] = $assignmentId;
        }
    }

    public function toggleShowAllUpcoming(): void
    {
        $this->showAllUpcoming = !$this->showAllUpcoming;
    }

    public function toggleShowAllPast(): void
    {
        $this->showAllPast = !$this->showAllPast;
    }

    public function startEdit(int $assignmentId): void
    {
        $assignment = Assignment::where('id', $assignmentId)
            ->where('trainer_id', auth()->id())
            ->firstOrFail();

        $this->editingAssignmentId = $assignment->id;
        $this->editDate            = $assignment->scheduled_date->toDateString();
        $this->editNotes           = $assignment->notes ?? '';
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editDate'  => 'required|date',
            'editNotes' => 'nullable|string',
        ]);

        Assignment::where('id', $this->editingAssignmentId)
            ->where('trainer_id', auth()->id())
            ->update([
                'scheduled_date' => $this->editDate,
                'notes'          => $this->editNotes ?: null,
            ]);

        $this->reset(['editingAssignmentId', 'editDate', 'editNotes']);
        Flux::toast(__('Assignment updated.'), variant: 'success');
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingAssignmentId', 'editDate', 'editNotes']);
    }

    public function deleteAssignment(int $id): void
    {
        Assignment::where('id', $id)
            ->where('trainer_id', auth()->id())
            ->delete();
        Flux::toast(__('Assignment deleted.'), variant: 'success');
    }

    public function render()
    {
        $assignments = Assignment::with([
                'workoutPlan.workoutPlanExercises.exercise',
                'exerciseLogs',
            ])
            ->where('client_id', $this->client->id)
            ->where('trainer_id', auth()->id())
            ->orderByDesc('scheduled_date')
            ->get();

        $upcoming = $assignments->where('scheduled_date', '>=', today());
        $past     = $assignments->where('scheduled_date', '<', today());

        $totalAssignments  = $assignments->count();
        $totalCompleted    = $assignments->filter(fn($a) => $a->isCompleted())->count();
        $avgCompletion     = $totalAssignments > 0
            ? (int) round($assignments->avg(fn($a) => $a->completionPercent()))
            : 0;

        // Body weight tracking
        $weightLogs = \App\Models\BodyWeightLog::where('client_id', $this->client->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $chartData = $weightLogs->take(10)->reverse()->values();
        $currentWeight = $weightLogs->first()?->weight;
        $startWeight = $weightLogs->last()?->weight;
        $weightChange = $currentWeight && $startWeight ? $currentWeight - $startWeight : 0;
        $lowestWeight = $weightLogs->min('weight');
        $highestWeight = $weightLogs->max('weight');

        return view('pages.trainer.client-detail', compact(
            'upcoming', 'past', 'totalAssignments', 'totalCompleted', 'avgCompletion',
            'weightLogs', 'chartData', 'currentWeight', 'startWeight', 'weightChange', 'lowestWeight', 'highestWeight'
        ));
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" size="sm" tag="a" :href="route('trainer.clients')">
            {{ __('Clients') }}
        </flux:button>
        <flux:separator vertical class="h-5" />
        <flux:heading size="xl">{{ $client->name }}</flux:heading>
    </div>

    {{-- Client info + stats --}}
    <div class="grid gap-4 sm:grid-cols-4">
        <flux:card class="sm:col-span-1 flex flex-col gap-3">
            <flux:avatar :name="$client->name" :initials="$client->initials()" size="lg" />
            <div>
                <div class="font-semibold">{{ $client->name }}</div>
                <div class="text-sm text-zinc-500">{{ $client->email }}</div>
                <div class="text-xs text-zinc-400 mt-1">{{ __('Member since :date', ['date' => $client->created_at->format('M Y')]) }}</div>
            </div>
            <flux:button size="sm" variant="ghost" icon="calendar-days" tag="a"
                :href="route('trainer.assign')">
                {{ __('Assign Workout') }}
            </flux:button>
        </flux:card>

        <flux:card class="flex flex-col justify-center gap-1">
            <flux:text class="text-sm text-zinc-500">{{ __('Total Assigned') }}</flux:text>
            <div class="text-3xl font-bold">{{ $totalAssignments }}</div>
        </flux:card>

        <flux:card class="flex flex-col justify-center gap-1">
            <flux:text class="text-sm text-zinc-500">{{ __('Fully Completed') }}</flux:text>
            <div class="text-3xl font-bold text-green-600">{{ $totalCompleted }}</div>
        </flux:card>

        <flux:card class="flex flex-col justify-center gap-1">
            <flux:text class="text-sm text-zinc-500">{{ __('Avg Completion') }}</flux:text>
            <div class="text-3xl font-bold {{ $avgCompletion >= 80 ? 'text-green-600' : ($avgCompletion >= 50 ? 'text-yellow-500' : 'text-zinc-400') }}">
                {{ $avgCompletion }}%
            </div>
        </flux:card>
    </div>

    {{-- Body Weight Card --}}
    @if ($weightLogs->isNotEmpty())
        <flux:card class="relative overflow-hidden">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <flux:heading size="lg">{{ __('Body Weight') }}</flux:heading>
                    @if ($client->goal)
                        <div class="flex items-center gap-2 mt-1">
                            <flux:icon name="flag" class="size-3 text-zinc-400" />
                            <span class="text-xs text-zinc-500">
                                @switch($client->goal)
                                    @case('weight_loss') {{ __('Weight Loss') }} @break
                                    @case('muscle_building') {{ __('Muscle Building') }} @break
                                    @case('maintenance') {{ __('Maintenance') }} @break
                                    @case('strength') {{ __('Strength') }} @break
                                    @case('endurance') {{ __('Endurance') }} @break
                                    @case('general_fitness') {{ __('General Fitness') }} @break
                                @endswitch
                                @if ($client->target_weight > 0)
                                    · {{ __('Target') }}: {{ $client->target_weight }} {{ $client->target_weight_unit }}
                                @endif
                            </span>
                        </div>
                    @endif
                </div>

                <div class="text-right">
                    <div class="text-3xl font-bold">{{ $currentWeight }}</div>
                    <div class="text-sm text-zinc-500">{{ $weightLogs->first()->unit }}</div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 mb-4">
                <div>
                    <div class="text-xs text-zinc-500">{{ __('Change') }}</div>
                    <div class="text-lg font-semibold {{ $weightChange > 0 ? 'text-red-600' : ($weightChange < 0 ? 'text-green-600' : 'text-zinc-400') }}">
                        {{ $weightChange > 0 ? '+' : '' }}{{ number_format($weightChange, 1) }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-zinc-500">{{ __('Lowest') }}</div>
                    <div class="text-lg font-semibold">{{ $lowestWeight }}</div>
                </div>
                <div>
                    <div class="text-xs text-zinc-500">{{ __('Highest') }}</div>
                    <div class="text-lg font-semibold">{{ $highestWeight }}</div>
                </div>
            </div>

            {{-- Mini Sparkline Chart --}}
            @if ($chartData->isNotEmpty())
                @php
                    $range = $highestWeight - $lowestWeight;
                    $points = [];
                    $count = $chartData->count();
                    foreach ($chartData as $index => $log) {
                        $x = ($index / max($count - 1, 1)) * 100;
                        if ($range == 0) {
                            $y = 50;
                        } else {
                            $normalized = ($log->weight - $lowestWeight) / $range;
                            $y = 100 - (($normalized * 80) + 10);
                        }
                        $points[] = ['x' => $x, 'y' => $y];
                    }
                    $pathData = '';
                    foreach ($points as $i => $point) {
                        $pathData .= ($i === 0 ? "M " : " L ") . "{$point['x']} {$point['y']}";
                    }
                @endphp
                <div class="relative h-16 -mx-6 -mb-6 mt-2">
                    <svg class="w-full h-full" preserveAspectRatio="none" viewBox="0 0 100 100">
                        <defs>
                            <linearGradient id="sparkGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:rgb(59, 130, 246);stop-opacity:0.2" />
                                <stop offset="100%" style="stop-color:rgb(59, 130, 246);stop-opacity:0" />
                            </linearGradient>
                        </defs>
                        <path d="{{ $pathData }} L 100 100 L 0 100 Z" fill="url(#sparkGradient)"/>
                        <path d="{{ $pathData }}" fill="none" stroke="rgb(59, 130, 246)" stroke-width="0.5" vector-effect="non-scaling-stroke"/>
                    </svg>
                    <div class="absolute bottom-0 left-0 right-0 px-6 pb-2 flex justify-between text-xs text-zinc-400">
                        <span>{{ $weightLogs->last()->created_at->format('M j') }}</span>
                        <span>{{ $weightLogs->first()->created_at->format('M j') }}</span>
                    </div>
                </div>
            @endif
        </flux:card>
    @endif

    {{-- Upcoming --}}
    @if ($upcoming->isNotEmpty())
        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Upcoming') }}</flux:heading>
                @if ($upcoming->count() > $upcomingLimit)
                    <flux:button wire:click="toggleShowAllUpcoming" variant="ghost" size="sm">
                        @if ($showAllUpcoming)
                            {{ __('Show Less') }}
                        @else
                            {{ __('Show All') }} ({{ $upcoming->count() }})
                        @endif
                    </flux:button>
                @endif
            </div>

            @php
                $displayUpcoming = $showAllUpcoming
                    ? $upcoming->sortBy('scheduled_date')
                    : $upcoming->sortBy('scheduled_date')->take($upcomingLimit);
            @endphp

            @foreach ($displayUpcoming as $a)
                @include('pages.trainer.partials.assignment-row', ['a' => $a])
            @endforeach

            @if (!$showAllUpcoming && $upcoming->count() > $upcomingLimit)
                <div class="text-center py-2">
                    <flux:button wire:click="toggleShowAllUpcoming" variant="subtle" size="sm">
                        {{ __('Show :count more', ['count' => $upcoming->count() - $upcomingLimit]) }}
                    </flux:button>
                </div>
            @endif
        </div>
    @endif

    {{-- Past --}}
    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">{{ __('Past Workouts') }}</flux:heading>
            @if ($past->count() > $pastLimit)
                <flux:button wire:click="toggleShowAllPast" variant="ghost" size="sm">
                    @if ($showAllPast)
                        {{ __('Show Less') }}
                    @else
                        {{ __('Show All') }} ({{ $past->count() }})
                    @endif
                </flux:button>
            @endif
        </div>

        @forelse ($past as $index => $a)
            @if ($showAllPast || $index < $pastLimit)
                @include('pages.trainer.partials.assignment-row', ['a' => $a])
            @endif
        @empty
            <flux:text class="text-zinc-500">{{ __('No past workouts yet.') }}</flux:text>
        @endforelse

        @if (!$showAllPast && $past->count() > $pastLimit)
            <div class="text-center py-2">
                <flux:button wire:click="toggleShowAllPast" variant="subtle" size="sm">
                    {{ __('Show :count more', ['count' => $past->count() - $pastLimit]) }}
                </flux:button>
            </div>
        @endif
    </div>

</div>
