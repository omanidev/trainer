<?php

use App\Models\BodyWeightLog;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('My Progress')] class extends Component {

    public float $weight = 0;
    public string $weightUnit = 'kg';
    public string $notes = '';
    public string $logDate = '';

    // Goal settings
    public string $goal = '';
    public float $targetWeight = 0;
    public string $targetWeightUnit = 'kg';

    public function mount(): void
    {
        $this->logDate = today()->toDateString();

        $user = auth()->user();

        // Get user's preferred unit from last log
        $lastLog = BodyWeightLog::where('client_id', auth()->id())
            ->latest()
            ->first();

        if ($lastLog) {
            $this->weightUnit = $lastLog->unit;
        }

        // Load goal settings
        $this->goal = $user->goal ?? '';
        $this->targetWeight = $user->target_weight ?? 0;
        $this->targetWeightUnit = $user->target_weight_unit ?? 'kg';
    }

    public function openGoalModal(): void
    {
        Flux::modal('goal-modal')->show();
    }

    public function closeGoalModal(): void
    {
        Flux::modal('goal-modal')->close();
    }

    public function saveGoal(): void
    {
        $this->validate([
            'goal' => 'required|in:weight_loss,muscle_building,maintenance,strength,endurance,general_fitness',
            'targetWeight' => 'nullable|numeric|min:1|max:500',
            'targetWeightUnit' => 'required|in:kg,lbs',
        ]);

        auth()->user()->update([
            'goal' => $this->goal,
            'target_weight' => $this->targetWeight ?: null,
            'target_weight_unit' => $this->targetWeightUnit,
        ]);

        Flux::modal('goal-modal')->close();
        Flux::toast(__('Goal updated successfully!'), variant: 'success');
    }

    public function logWeight(): void
    {
        $this->validate([
            'weight' => 'required|numeric|min:1|max:500',
            'weightUnit' => 'required|in:kg,lbs',
            'logDate' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $log = new BodyWeightLog([
            'client_id' => auth()->id(),
            'weight' => $this->weight,
            'unit' => $this->weightUnit,
            'notes' => $this->notes ?: null,
        ]);

        // Set the created_at and updated_at to the selected date
        $log->created_at = $this->logDate;
        $log->updated_at = $this->logDate;
        $log->save();

        $this->reset(['weight', 'notes']);
        $this->logDate = today()->toDateString();
        Flux::toast(__('Weight logged successfully!'), variant: 'success');
    }

    public function deleteLog(int $id): void
    {
        BodyWeightLog::where('id', $id)
            ->where('client_id', auth()->id())
            ->delete();

        Flux::toast(__('Weight log deleted.'), variant: 'success');
    }

    public function render()
    {
        $logs = BodyWeightLog::where('client_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        // Prepare chart data (last 10 entries, reversed for chronological order)
        $chartData = $logs->take(10)->reverse()->values();

        // Calculate stats
        $currentWeight = $logs->first()?->weight;
        $startWeight = $logs->last()?->weight;
        $weightChange = $currentWeight && $startWeight ? $currentWeight - $startWeight : 0;
        $lowestWeight = $logs->min('weight');
        $highestWeight = $logs->max('weight');

        return view('pages.client.progress', compact('logs', 'chartData', 'currentWeight', 'startWeight', 'weightChange', 'lowestWeight', 'highestWeight'));
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6 max-w-4xl mx-auto">

    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="xl">{{ __('Body Weight Progress') }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('Track your body weight over time') }}</flux:text>
        </div>
        <flux:button wire:click="openGoalModal" variant="ghost" icon="flag">
            {{ __('My Goal') }}
        </flux:button>
    </div>

    {{-- Goal Display --}}
    @if ($goal)
        <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/30 p-5">
            <div class="flex items-center gap-2 mb-2">
                <flux:icon name="flag" class="size-5 text-green-600 dark:text-green-400" />
                <flux:heading size="lg">{{ __('Current Goal') }}</flux:heading>
            </div>
            <div class="flex items-center gap-4">
                <div>
                    <span class="text-lg font-semibold">
                        @switch($goal)
                            @case('weight_loss')
                                {{ __('Weight Loss') }}
                                @break
                            @case('muscle_building')
                                {{ __('Muscle Building') }}
                                @break
                            @case('maintenance')
                                {{ __('Maintenance') }}
                                @break
                            @case('strength')
                                {{ __('Strength') }}
                                @break
                            @case('endurance')
                                {{ __('Endurance') }}
                                @break
                            @case('general_fitness')
                                {{ __('General Fitness') }}
                                @break
                        @endswitch
                    </span>
                    @if ($targetWeight > 0)
                        <span class="text-zinc-600 dark:text-zinc-400 ml-2">
                            {{ __('Target') }}: <strong>{{ $targetWeight }} {{ $targetWeightUnit }}</strong>
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Log weight card --}}
    <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 p-5">
        <div class="flex items-center gap-2 mb-4">
            <flux:icon name="scale" class="size-5 text-blue-600 dark:text-blue-400" />
            <flux:heading size="lg">{{ __('Log Your Weight') }}</flux:heading>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <flux:input wire:model="weight" type="number" step="0.1" min="1" max="500"
                    :placeholder="__('Enter weight')" />
            </div>
            <flux:input wire:model="logDate" type="date" class="sm:w-40" :max="date('Y-m-d')" />
            <flux:select wire:model="weightUnit" class="sm:w-24">
                <flux:select.option value="kg">kg</flux:select.option>
                <flux:select.option value="lbs">lbs</flux:select.option>
            </flux:select>
            <flux:button wire:click="logWeight" variant="primary" icon="check">
                {{ __('Log Weight') }}
            </flux:button>
        </div>

        <div class="mt-3">
            <flux:input wire:model="notes" :placeholder="__('Notes (optional)')" />
        </div>
    </div>

    {{-- Stats cards --}}
    @if ($logs->isNotEmpty())
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                <div class="text-xs text-zinc-500 mb-1">{{ __('Current') }}</div>
                <div class="text-2xl font-bold">{{ $currentWeight }} {{ $logs->first()->unit }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                <div class="text-xs text-zinc-500 mb-1">{{ __('Change') }}</div>
                <div class="text-2xl font-bold {{ $weightChange > 0 ? 'text-red-600' : ($weightChange < 0 ? 'text-green-600' : '') }}">
                    {{ $weightChange > 0 ? '+' : '' }}{{ number_format($weightChange, 1) }} {{ $logs->first()->unit }}
                </div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                <div class="text-xs text-zinc-500 mb-1">{{ __('Lowest') }}</div>
                <div class="text-2xl font-bold">{{ $lowestWeight }} {{ $logs->first()->unit }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                <div class="text-xs text-zinc-500 mb-1">{{ __('Highest') }}</div>
                <div class="text-2xl font-bold">{{ $highestWeight }} {{ $logs->first()->unit }}</div>
            </div>
        </div>

        {{-- Chart --}}
        @if ($chartData->isNotEmpty())
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5">
                <flux:heading size="lg" class="mb-4">{{ __('Weight Trend') }}</flux:heading>
                <div class="relative" style="height: 280px;">
                    @php
                        $chartHeight = 200;
                        $chartWidth = 100; // percentage
                        $range = $highestWeight - $lowestWeight;
                        $padding = 20;

                        // Calculate points for the line
                        $points = [];
                        $count = $chartData->count();
                        foreach ($chartData as $index => $log) {
                            $x = ($index / max($count - 1, 1)) * 100; // percentage

                            if ($range == 0) {
                                $y = 50; // center
                            } else {
                                $normalized = ($log->weight - $lowestWeight) / $range;
                                $y = 100 - (($normalized * 80) + 10); // invert Y axis, use 10-90% range
                            }

                            $points[] = [
                                'x' => $x,
                                'y' => $y,
                                'weight' => $log->weight,
                                'date' => $log->created_at->format('M j')
                            ];
                        }

                        // Create SVG path
                        $pathData = '';
                        foreach ($points as $i => $point) {
                            if ($i === 0) {
                                $pathData .= "M {$point['x']} {$point['y']}";
                            } else {
                                $pathData .= " L {$point['x']} {$point['y']}";
                            }
                        }
                    @endphp

                    <svg class="w-full" style="height: {{ $chartHeight }}px;" preserveAspectRatio="none" viewBox="0 0 100 100">
                        {{-- Grid lines --}}
                        @for ($i = 0; $i <= 4; $i++)
                            <line x1="0" y1="{{ $i * 25 }}" x2="100" y2="{{ $i * 25 }}"
                                  stroke="currentColor" stroke-width="0.1" opacity="0.1" vector-effect="non-scaling-stroke"/>
                        @endfor

                        {{-- Line path --}}
                        <path d="{{ $pathData }}"
                              fill="none"
                              stroke="rgb(59, 130, 246)"
                              stroke-width="0.5"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              vector-effect="non-scaling-stroke"/>

                        {{-- Data points --}}
                        @foreach ($points as $point)
                            <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="0.8"
                                    fill="rgb(59, 130, 246)"
                                    stroke="white"
                                    stroke-width="0.3"
                                    vector-effect="non-scaling-stroke"/>
                        @endforeach
                    </svg>

                    {{-- Labels --}}
                    <div class="flex justify-between mt-3">
                        @foreach ($points as $point)
                            <div class="flex-1 text-center">
                                <div class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $point['weight'] }}
                                </div>
                                <div class="text-xs text-zinc-500 mt-1">
                                    {{ $point['date'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- History list --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-800">
                <flux:heading size="lg">{{ __('History') }}</flux:heading>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($logs as $log)
                    <div class="flex items-center justify-between px-5 py-4 group hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                        <div class="flex-1">
                            <div class="flex items-baseline gap-2">
                                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ $log->weight }} {{ $log->unit }}
                                </span>
                            </div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-0.5">
                                {{ $log->created_at->format('M j, Y') }} · {{ $log->created_at->diffForHumans() }}
                            </div>
                            @if ($log->notes)
                                <div class="text-sm text-zinc-500 mt-1">{{ $log->notes }}</div>
                            @endif
                        </div>
                        <button wire:click="deleteLog({{ $log->id }})"
                            wire:confirm="{{ __('Delete this weight log?') }}"
                            class="opacity-0 group-hover:opacity-100 transition text-red-500 hover:text-red-700 p-2">
                            <flux:icon name="trash" class="size-4" />
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center gap-4 py-20 text-center">
            <div class="flex size-16 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="scale" class="size-8 text-zinc-400" />
            </div>
            <div>
                <flux:heading>{{ __('No weight data yet') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Start tracking your body weight to see your progress.') }}</flux:text>
            </div>
        </div>
    @endif

    {{-- Goal Modal --}}
    <flux:modal name="goal-modal" class="space-y-6 w-full max-w-md">
        <div>
            <flux:heading size="lg">{{ __('Set Your Fitness Goal') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Choose your primary fitness objective and optional target weight') }}</flux:text>
        </div>

        <div class="space-y-4">
            <div>
                <flux:label>{{ __('Goal') }}</flux:label>
                <flux:select wire:model="goal">
                    <flux:select.option value="">{{ __('Select a goal') }}</flux:select.option>
                    <flux:select.option value="weight_loss">{{ __('Weight Loss') }}</flux:select.option>
                    <flux:select.option value="muscle_building">{{ __('Muscle Building') }}</flux:select.option>
                    <flux:select.option value="maintenance">{{ __('Maintenance') }}</flux:select.option>
                    <flux:select.option value="strength">{{ __('Strength') }}</flux:select.option>
                    <flux:select.option value="endurance">{{ __('Endurance') }}</flux:select.option>
                    <flux:select.option value="general_fitness">{{ __('General Fitness') }}</flux:select.option>
                </flux:select>
            </div>

            <div>
                <flux:label>{{ __('Target Weight (Optional)') }}</flux:label>
                <div class="flex gap-2">
                    <flux:input wire:model="targetWeight" type="number" step="0.1" min="1" max="500"
                        :placeholder="__('Enter target weight')" class="flex-1" />
                    <flux:select wire:model="targetWeightUnit" class="w-24">
                        <flux:select.option value="kg">kg</flux:select.option>
                        <flux:select.option value="lbs">lbs</flux:select.option>
                    </flux:select>
                </div>
            </div>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:button wire:click="closeGoalModal" variant="ghost">
                {{ __('Cancel') }}
            </flux:button>
            <flux:button wire:click="saveGoal" variant="primary">
                {{ __('Save Goal') }}
            </flux:button>
        </div>
    </flux:modal>

</div>
