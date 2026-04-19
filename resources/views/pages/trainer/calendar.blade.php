<?php

use App\Models\Assignment;
use App\Models\User;
use App\Models\WorkoutPlan;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Calendar')] class extends Component {

    public string $view = 'month'; // 'month' or 'week'
    public Carbon $currentDate;
    public array $clientColors = [];

    // Filter
    public ?int $filterClientId = null;

    // Quick assign modal
    public bool $showAssignModal = false;
    public ?string $selectedDate = null;
    public ?int $selectedClientId = null;
    public ?int $selectedPlanId = null;
    public string $assignNotes = '';

    public function mount(): void
    {
        $this->currentDate = Carbon::now();
        $this->assignClientColors();
    }

    public function assignClientColors(): void
    {
        $clients = User::where('trainer_id', auth()->id())
            ->where('role', 'client')
            ->orderBy('name')
            ->get();

        $colors = [
            'bg-blue-100 border-blue-300 text-blue-800',
            'bg-green-100 border-green-300 text-green-800',
            'bg-purple-100 border-purple-300 text-purple-800',
            'bg-orange-100 border-orange-300 text-orange-800',
            'bg-pink-100 border-pink-300 text-pink-800',
            'bg-teal-100 border-teal-300 text-teal-800',
            'bg-indigo-100 border-indigo-300 text-indigo-800',
            'bg-rose-100 border-rose-300 text-rose-800',
        ];

        foreach ($clients as $index => $client) {
            $this->clientColors[$client->id] = $colors[$index % count($colors)];
        }
    }

    public function toggleView(): void
    {
        $this->view = $this->view === 'month' ? 'week' : 'month';
    }

    public function previousPeriod(): void
    {
        if ($this->view === 'month') {
            $this->currentDate = $this->currentDate->copy()->subMonth();
        } else {
            $this->currentDate = $this->currentDate->copy()->subWeek();
        }
    }

    public function nextPeriod(): void
    {
        if ($this->view === 'month') {
            $this->currentDate = $this->currentDate->copy()->addMonth();
        } else {
            $this->currentDate = $this->currentDate->copy()->addWeek();
        }
    }

    public function goToToday(): void
    {
        $this->currentDate = Carbon::now();
    }

    public function openAssignModal(string $date): void
    {
        $this->selectedDate = $date;
        $this->selectedClientId = null;
        $this->selectedPlanId = null;
        $this->assignNotes = '';
        $this->showAssignModal = true;
    }

    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
        $this->reset(['selectedDate', 'selectedClientId', 'selectedPlanId', 'assignNotes']);
    }

    public function quickAssign(): void
    {
        $this->validate([
            'selectedClientId' => 'required|exists:users,id',
            'selectedPlanId' => 'required|exists:workout_plans,id',
            'selectedDate' => 'required|date',
            'assignNotes' => 'nullable|string|max:500',
        ]);

        Assignment::create([
            'trainer_id' => auth()->id(),
            'client_id' => $this->selectedClientId,
            'workout_plan_id' => $this->selectedPlanId,
            'scheduled_date' => $this->selectedDate,
            'notes' => $this->assignNotes ?: null,
        ]);

        Flux::toast(__('Workout assigned successfully!'), variant: 'success');
        $this->closeAssignModal();
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
        $clients = User::where('trainer_id', auth()->id())
            ->where('role', 'client')
            ->orderBy('name')
            ->get();

        $plans = WorkoutPlan::where('trainer_id', auth()->id())
            ->orderBy('name')
            ->get();

        if ($this->view === 'month') {
            // Get calendar grid for the month
            $startOfMonth = $this->currentDate->copy()->startOfMonth();
            $endOfMonth = $this->currentDate->copy()->endOfMonth();

            // Start from the beginning of the week containing the first day
            $startDate = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);

            // End at the end of the week containing the last day
            $endDate = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);

            $days = [];
            $currentDay = $startDate->copy();

            while ($currentDay <= $endDate) {
                $days[] = $currentDay->copy();
                $currentDay->addDay();
            }
        } else {
            // Week view
            $startDate = $this->currentDate->copy()->startOfWeek(Carbon::SUNDAY);
            $endDate = $this->currentDate->copy()->endOfWeek(Carbon::SATURDAY);

            $days = [];
            $currentDay = $startDate->copy();

            while ($currentDay <= $endDate) {
                $days[] = $currentDay->copy();
                $currentDay->addDay();
            }
        }

        // Load assignments for the date range
        $query = Assignment::with(['client', 'workoutPlan', 'exerciseLogs'])
            ->where('trainer_id', auth()->id())
            ->whereBetween('scheduled_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        // Apply client filter if selected
        if ($this->filterClientId) {
            $query->where('client_id', $this->filterClientId);
        }

        $assignments = $query->get()
            ->groupBy(fn($a) => $a->scheduled_date->format('Y-m-d'));

        return view('pages.trainer.calendar', compact('days', 'assignments', 'clients', 'plans'));
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4 sm:gap-6 p-3 sm:p-6">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:gap-0 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <flux:heading size="xl" class="text-lg sm:text-2xl">{{ __('Calendar') }}</flux:heading>
            <flux:separator vertical class="hidden sm:block h-6" />
            <div class="flex items-center gap-2">
                <flux:button wire:click="previousPeriod" variant="ghost" size="sm" icon="chevron-left" />
                <div class="min-w-32 sm:min-w-48 text-center">
                    <span class="text-sm sm:text-lg font-semibold">
                        @if ($view === 'month')
                            {{ $currentDate->format('F Y') }}
                        @else
                            <span class="hidden sm:inline">{{ $currentDate->copy()->startOfWeek()->format('M j') }} - {{ $currentDate->copy()->endOfWeek()->format('M j, Y') }}</span>
                            <span class="sm:hidden">{{ $currentDate->copy()->startOfWeek()->format('M j') }} - {{ $currentDate->copy()->endOfWeek()->format('M j') }}</span>
                        @endif
                    </span>
                </div>
                <flux:button wire:click="nextPeriod" variant="ghost" size="sm" icon="chevron-right" />
            </div>
            <flux:button wire:click="goToToday" variant="ghost" size="sm" class="w-full sm:w-auto">
                {{ __('Today') }}
            </flux:button>
        </div>

        <div class="flex items-center gap-2">
            <div class="flex-1 sm:flex-initial sm:min-w-48">
                <flux:select wire:model.live="filterClientId" size="sm">
                    <flux:select.option value="">{{ __('All Clients') }}</flux:select.option>
                    @foreach($clients as $client)
                        <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:button wire:click="toggleView" variant="ghost" size="sm" :icon="$view === 'month' ? 'calendar-days' : 'calendar'" class="shrink-0">
                <span class="hidden sm:inline">{{ $view === 'month' ? __('Week View') : __('Month View') }}</span>
                <span class="sm:hidden">{{ $view === 'month' ? __('Week') : __('Month') }}</span>
            </flux:button>
        </div>
    </div>

    {{-- Calendar Grid --}}
    @if ($view === 'month')
        {{-- Month View --}}
        <flux:card class="overflow-hidden">
            <div class="overflow-x-auto">
                <div class="min-w-160">
                    <div class="grid grid-cols-7 border-b border-zinc-200 dark:border-zinc-700">
                        @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
                            <div class="p-2 sm:p-3 text-center text-xs sm:text-sm font-semibold border-r border-zinc-200 dark:border-zinc-700 last:border-r-0">
                                <span class="hidden sm:inline">{{ __($day) }}</span>
                                <span class="sm:hidden">{{ __(substr($day, 0, 3)) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-7">
                        @foreach($days as $day)
                            @php
                                $isToday = $day->isToday();
                                $isCurrentMonth = $day->month === $currentDate->month;
                                $dateKey = $day->format('Y-m-d');
                                $dayAssignments = $assignments->get($dateKey, collect());
                            @endphp

                            <div class="min-h-24 sm:min-h-32 border-r border-b border-zinc-200 dark:border-zinc-700 last:border-r-0 {{ !$isCurrentMonth ? 'bg-zinc-50 dark:bg-zinc-900/50' : 'bg-white dark:bg-zinc-900' }}">
                                <div class="p-1 sm:p-2">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs sm:text-sm font-medium {{ $isToday ? 'flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-blue-600 text-white text-xs' : ($isCurrentMonth ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-400') }}">
                                            {{ $day->day }}
                                        </span>
                                        @if ($isCurrentMonth)
                                            <button wire:click="openAssignModal('{{ $dateKey }}')" class="text-zinc-400 hover:text-blue-600 transition">
                                                <flux:icon name="plus" class="size-3 sm:size-4" />
                                            </button>
                                        @endif
                                    </div>

                                    <div class="space-y-0.5 sm:space-y-1">
                                        @foreach($dayAssignments->take(2) as $assignment)
                                            <div wire:click="$dispatch('open-assignment-{{ $assignment->id }}')"
                                                 class="text-xs p-0.5 sm:p-1 rounded cursor-pointer border {{ $clientColors[$assignment->client_id] ?? 'bg-gray-100 border-gray-300 text-gray-800' }} truncate hover:shadow-sm transition"
                                                 title="{{ $assignment->client->name }} - {{ $assignment->workoutPlan->name }}">
                                                <div class="font-medium truncate text-[10px] sm:text-xs">{{ $assignment->client->name }}</div>
                                                @if ($assignment->isCompleted())
                                                    <span class="text-[9px] sm:text-xs">✓</span>
                                                @elseif ($day->isPast())
                                                    <span class="text-[9px] sm:text-xs text-red-600">✗</span>
                                                @endif
                                            </div>
                                        @endforeach

                                        @if($dayAssignments->count() > 2)
                                            <div class="text-[9px] sm:text-xs text-zinc-500 pl-0.5 sm:pl-1">
                                                +{{ $dayAssignments->count() - 2 }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </flux:card>
    @else
        {{-- Week View --}}
        <flux:card class="overflow-hidden">
            <div class="overflow-x-auto">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-2 sm:gap-3 p-3 sm:p-4 min-w-160 lg:min-w-0">
                    @foreach($days as $day)
                        @php
                            $isToday = $day->isToday();
                            $dateKey = $day->format('Y-m-d');
                            $dayAssignments = $assignments->get($dateKey, collect());
                        @endphp

                        <div class="min-h-64 sm:min-h-80 lg:min-h-96">
                            <div class="flex items-center justify-between mb-2 sm:mb-3 pb-2 border-b border-zinc-200 dark:border-zinc-700">
                                <div>
                                    <div class="text-xs text-zinc-500">{{ $day->format('D') }}</div>
                                    <div class="{{ $isToday ? 'flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-blue-600 text-white font-bold text-lg sm:text-xl' : 'text-xl sm:text-2xl font-semibold' }}">
                                        {{ $day->day }}
                                    </div>
                                </div>
                                <button wire:click="openAssignModal('{{ $dateKey }}')" class="text-zinc-400 hover:text-blue-600 transition">
                                    <flux:icon name="plus" class="size-4 sm:size-5" />
                                </button>
                            </div>

                            <div class="space-y-1.5 sm:space-y-2">
                                @forelse($dayAssignments as $assignment)
                                    <div wire:click="$dispatch('open-assignment-{{ $assignment->id }}')"
                                         class="text-xs p-1.5 sm:p-2 rounded cursor-pointer border {{ $clientColors[$assignment->client_id] ?? 'bg-gray-100 border-gray-300 text-gray-800' }} hover:shadow-md transition">
                                        <div class="font-semibold mb-1 text-xs sm:text-sm">{{ $assignment->client->name }}</div>
                                        <div class="text-[10px] sm:text-xs opacity-90 truncate" title="{{ $assignment->workoutPlan->name }}">
                                            {{ $assignment->workoutPlan->name }}
                                        </div>
                                        <div class="mt-1 pt-1 border-t border-black/10">
                                            @if ($assignment->isCompleted())
                                                <span class="text-[10px] sm:text-xs font-medium">✓ {{ __('Completed') }}</span>
                                            @elseif ($day->isPast())
                                                <span class="text-[10px] sm:text-xs font-medium text-red-600">✗ {{ __('Missed') }}</span>
                                            @else
                                                <span class="text-[10px] sm:text-xs opacity-75">{{ __('Scheduled') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-zinc-400 text-xs py-4 sm:py-8">
                                        {{ __('No workouts') }}
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </flux:card>
    @endif

    {{-- Quick Assign Modal --}}
    @if ($showAssignModal)
        <flux:modal name="assign-modal" class="space-y-6 w-full max-w-md" variant="flyout" position="right">
            <div>
                <flux:heading size="lg">{{ __('Assign Workout') }}</flux:heading>
                <flux:text class="mt-1">{{ $selectedDate ? Carbon::parse($selectedDate)->format('l, F j, Y') : '' }}</flux:text>
            </div>

            <div class="space-y-4">
                <div>
                    <flux:label>{{ __('Client') }}</flux:label>
                    <flux:select wire:model="selectedClientId">
                        <flux:select.option value="">{{ __('Select client') }}</flux:select.option>
                        @foreach($clients as $client)
                            <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <flux:label>{{ __('Workout Plan') }}</flux:label>
                    <flux:select wire:model="selectedPlanId">
                        <flux:select.option value="">{{ __('Select workout plan') }}</flux:select.option>
                        @foreach($plans as $plan)
                            <flux:select.option value="{{ $plan->id }}">{{ $plan->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <flux:label>{{ __('Notes (Optional)') }}</flux:label>
                    <flux:input wire:model="assignNotes" :placeholder="__('Add notes...')" />
                </div>
            </div>

            <div class="flex gap-2 justify-end">
                <flux:button wire:click="closeAssignModal" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button wire:click="quickAssign" variant="primary">
                    {{ __('Assign Workout') }}
                </flux:button>
            </div>
        </flux:modal>

        <div x-data x-init="$wire.showAssignModal && Flux.modal('assign-modal').show()"></div>
    @endif

</div>
