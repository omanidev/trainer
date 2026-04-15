<?php

use App\Models\Assignment;
use App\Models\WorkoutPlan;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Assign Workout')] class extends Component {

    public int    $clientId   = 0;
    public int    $planId     = 0;
    public string $startDate  = '';
    public string $endDate    = '';
    public string $notes      = '';
    public bool   $isPeriod   = false;

    // Day-of-week checkboxes (0=Sun … 6=Sat)
    public array $weekDays = [];

    const DAY_LABELS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    public function mount(): void
    {
        $this->startDate = now()->toDateString();
        $this->endDate   = now()->addWeeks(4)->toDateString();
        $this->weekDays  = [1, 3, 5]; // Mon / Wed / Fri default
    }

    public function toggleDay(int $day): void
    {
        if (in_array($day, $this->weekDays)) {
            $this->weekDays = array_values(array_filter($this->weekDays, fn($d) => $d !== $day));
        } else {
            $this->weekDays[] = $day;
            sort($this->weekDays);
        }
    }

    public function assign(): void
    {
        $rules = [
            'clientId'  => 'required|integer|min:1',
            'planId'    => 'required|integer|min:1',
            'startDate' => 'required|date',
            'notes'     => 'nullable|string',
        ];

        if ($this->isPeriod) {
            $rules['endDate']  = 'required|date|after_or_equal:startDate';
            $rules['weekDays'] = 'required|array|min:1';
        }

        $this->validate($rules);

        $client = auth()->user()->clients()->findOrFail($this->clientId);
        $plan   = auth()->user()->workoutPlans()->findOrFail($this->planId);

        if ($this->isPeriod) {
            $seriesId = (string) Str::uuid();
            $start    = Carbon::parse($this->startDate);
            $end      = Carbon::parse($this->endDate);
            $count    = 0;

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                if (in_array($date->dayOfWeek, $this->weekDays)) {
                    Assignment::create([
                        'workout_plan_id' => $plan->id,
                        'client_id'       => $client->id,
                        'trainer_id'      => auth()->id(),
                        'scheduled_date'  => $date->toDateString(),
                        'notes'           => $this->notes ?: null,
                        'series_id'       => $seriesId,
                    ]);
                    $count++;
                }
            }

            Flux::toast(__('Assigned :count sessions to :name.', ['count' => $count, 'name' => $client->name]), variant: 'success');
        } else {
            Assignment::create([
                'workout_plan_id' => $plan->id,
                'client_id'       => $client->id,
                'trainer_id'      => auth()->id(),
                'scheduled_date'  => $this->startDate,
                'notes'           => $this->notes ?: null,
            ]);
            Flux::toast(__('Workout assigned to :name.', ['name' => $client->name]), variant: 'success');
        }

        $this->reset(['clientId', 'planId', 'notes', 'isPeriod']);
        $this->startDate = now()->toDateString();
        $this->endDate   = now()->addWeeks(4)->toDateString();
        $this->weekDays  = [1, 3, 5];
    }

    public function deleteAssignment(int $id): void
    {
        Assignment::where('id', $id)
            ->where('trainer_id', auth()->id())
            ->delete();
        Flux::toast(__('Assignment removed.'), variant: 'success');
    }

    public function deleteSeries(string $seriesId): void
    {
        $count = Assignment::where('trainer_id', auth()->id())
            ->where('series_id', $seriesId)
            ->count();

        Assignment::where('trainer_id', auth()->id())
            ->where('series_id', $seriesId)
            ->delete();

        Flux::toast(__('Deleted :count sessions in this series.', ['count' => $count]), variant: 'success');
    }

    public function render()
    {
        /** @var \App\Models\User $trainer */
        $trainer = auth()->user();

        // Group assignments: series together, singles standalone
        $rawAssignments = Assignment::with(['client', 'workoutPlan'])
            ->where('trainer_id', $trainer->id)
            ->orderByDesc('scheduled_date')
            ->get();

        // Build display rows: series grouped, singles as-is
        $displayRows = collect();
        $seenSeries  = [];

        foreach ($rawAssignments as $a) {
            if ($a->series_id) {
                if (!in_array($a->series_id, $seenSeries)) {
                    $seenSeries[] = $a->series_id;

                    // Gather all assignments in this series
                    $seriesItems = $rawAssignments->where('series_id', $a->series_id)->values();

                    $displayRows->push([
                        'type'      => 'series',
                        'seriesId'  => $a->series_id,
                        'client'    => $a->client,
                        'plan'      => $a->workoutPlan,
                        'count'     => $seriesItems->count(),
                        'startDate' => $seriesItems->min('scheduled_date'),
                        'endDate'   => $seriesItems->max('scheduled_date'),
                        'notes'     => $a->notes,
                    ]);
                }
            } else {
                $displayRows->push([
                    'type'       => 'single',
                    'assignment' => $a,
                ]);
            }
        }

        return view('pages.trainer.assign', [
            'clients'     => $trainer->clients()->orderBy('name')->get(),
            'plans'       => $trainer->workoutPlans()->orderBy('name')->get(),
            'displayRows' => $displayRows,
        ]);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    <flux:heading size="xl">{{ __('Assign Workout') }}</flux:heading>

    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Assignment form --}}
        <flux:card class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('New Assignment') }}</flux:heading>

            <flux:select wire:model="clientId" :label="__('Client')">
                <flux:select.option value="0">{{ __('— Select client —') }}</flux:select.option>
                @foreach ($clients as $client)
                    <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="planId" :label="__('Workout Plan')">
                <flux:select.option value="0">{{ __('— Select plan —') }}</flux:select.option>
                @foreach ($plans as $plan)
                    <flux:select.option value="{{ $plan->id }}">{{ $plan->name }}</flux:select.option>
                @endforeach
            </flux:select>

            {{-- Single vs Period toggle --}}
            <div class="flex items-center gap-3">
                <flux:label>{{ __('Schedule type') }}</flux:label>
                <div class="flex rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden text-sm">
                    <button wire:click="$set('isPeriod', false)"
                        class="px-3 py-1.5 transition {{ !$isPeriod ? 'bg-zinc-800 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                        {{ __('Single day') }}
                    </button>
                    <button wire:click="$set('isPeriod', true)"
                        class="px-3 py-1.5 transition {{ $isPeriod ? 'bg-zinc-800 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                        {{ __('Period') }}
                    </button>
                </div>
            </div>

            @if (!$isPeriod)
                <flux:input wire:model="startDate" :label="__('Date')" type="date" />
            @else
                <div class="grid grid-cols-2 gap-3">
                    <flux:input wire:model="startDate" :label="__('Start Date')" type="date" />
                    <flux:input wire:model="endDate" :label="__('End Date')" type="date" />
                </div>

                {{-- Day of week picker --}}
                <div>
                    <flux:label>{{ __('Repeat on days') }}</flux:label>
                    <div class="mt-2 flex gap-2 flex-wrap">
                        @foreach ([0,1,2,3,4,5,6] as $day)
                            <button type="button"
                                wire:click="toggleDay({{ $day }})"
                                class="w-10 h-10 rounded-full text-sm font-medium transition border
                                       {{ in_array($day, $weekDays)
                                           ? 'bg-zinc-800 text-white border-zinc-800 dark:bg-zinc-200 dark:text-zinc-900 dark:border-zinc-200'
                                           : 'bg-white text-zinc-500 border-zinc-200 hover:border-zinc-400 dark:bg-zinc-900 dark:border-zinc-700 dark:hover:border-zinc-500' }}">
                                {{ [__('Su'),__('Mo'),__('Tu'),__('We'),__('Th'),__('Fr'),__('Sa')][$day] }}
                            </button>
                        @endforeach
                    </div>
                    @if (empty($weekDays))
                        <p class="text-xs text-red-500 mt-1">{{ __('Select at least one day.') }}</p>
                    @endif
                </div>

                {{-- Preview session count --}}
                @php
                    $previewCount = 0;
                    if (!empty($weekDays) && $startDate && $endDate && $endDate >= $startDate) {
                        $d = \Carbon\Carbon::parse($startDate);
                        $e = \Carbon\Carbon::parse($endDate);
                        for ($dd = $d->copy(); $dd->lte($e); $dd->addDay()) {
                            if (in_array($dd->dayOfWeek, $weekDays)) {
                                $previewCount++;
                            }
                        }
                    }
                @endphp
                @if ($previewCount > 0)
                    <div class="rounded-lg bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 px-3 py-2 text-sm text-blue-700 dark:text-blue-300">
                        {{ __('This will create :count sessions between :start and :end.', [
                            'count' => $previewCount,
                            'start' => \Carbon\Carbon::parse($startDate)->format('M j'),
                            'end'   => \Carbon\Carbon::parse($endDate)->format('M j, Y'),
                        ]) }}
                    </div>
                @endif
            @endif

            <flux:textarea wire:model="notes" :label="__('Notes')" :placeholder="__('Optional message for the client…')" rows="2" />

            <flux:button variant="primary" wire:click="assign" class="self-end">
                {{ $isPeriod ? __('Assign Period') : __('Assign Workout') }}
            </flux:button>
        </flux:card>

        {{-- Assignments list --}}
        <div class="flex flex-col gap-3">
            <flux:heading size="lg">{{ __('Assignments') }}</flux:heading>

            @forelse ($displayRows as $row)
                @if ($row['type'] === 'series')
                    {{-- Series row --}}
                    <div class="flex items-start gap-3 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-950/30 px-4 py-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <div class="font-medium truncate">{{ $row['client']->name }}</div>
                                <flux:badge size="sm" color="blue">{{ $row['count'] }} {{ __('sessions') }}</flux:badge>
                            </div>
                            <div class="text-sm text-zinc-500 truncate mt-0.5">
                                {{ $row['plan']->name }}
                                · {{ \Carbon\Carbon::parse($row['startDate'])->format('M j') }}
                                – {{ \Carbon\Carbon::parse($row['endDate'])->format('M j, Y') }}
                            </div>
                            @if ($row['notes'])
                                <div class="text-xs text-zinc-400 mt-0.5 truncate">{{ $row['notes'] }}</div>
                            @endif
                        </div>
                        <flux:button size="sm" variant="ghost" icon="trash"
                            wire:click="deleteSeries('{{ $row['seriesId'] }}')"
                            :wire:confirm="__('Delete all :count sessions in this series?', ['count' => $row['count']])" />
                    </div>
                @else
                    {{-- Single assignment row --}}
                    @php $a = $row['assignment']; $pct = $a->completionPercent(); @endphp
                    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 px-4 py-3">
                        <div class="flex-1 min-w-0">
                            <div class="font-medium truncate">{{ $a->client->name }}</div>
                            <div class="text-sm text-zinc-500 truncate">
                                {{ $a->workoutPlan->name }} · {{ $a->scheduled_date->format('M j, Y') }}
                            </div>
                            @if ($a->notes)
                                <div class="text-xs text-zinc-400 mt-0.5 truncate">{{ $a->notes }}</div>
                            @endif
                        </div>
                        <div class="text-sm font-semibold shrink-0 {{ $pct === 100 ? 'text-green-600' : 'text-zinc-500' }}">
                            {{ $pct }}%
                        </div>
                        <flux:button size="sm" variant="ghost" icon="trash"
                            wire:click="deleteAssignment({{ $a->id }})"
                            :wire:confirm="__('Remove this assignment?')" />
                    </div>
                @endif
            @empty
                <flux:text class="text-zinc-500">{{ __('No assignments yet.') }}</flux:text>
            @endforelse
        </div>
    </div>
</div>
