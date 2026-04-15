@php
    $pct         = $a->completionPercent();
    $isCompleted = $a->isCompleted();
    $isExpanded  = in_array($a->id, $this->expanded);
    $isEditing   = $this->editingAssignmentId === $a->id;
    $logs        = $a->exerciseLogs->keyBy('workout_plan_exercise_id');
    $items       = $a->workoutPlan->workoutPlanExercises;
    $isToday     = $a->scheduled_date->isToday();
    $isFuture    = $a->scheduled_date->isFuture();
@endphp

<div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">

    {{-- Row header --}}
    @if ($isEditing)
        {{-- Edit mode --}}
        <div class="flex flex-col gap-3 px-4 py-4 bg-blue-50 dark:bg-blue-950/30">
            <div class="flex items-center gap-2">
                <flux:icon name="pencil" class="size-4 text-blue-500" />
                <span class="font-medium text-blue-700 dark:text-blue-400">{{ __('Editing — :plan', ['plan' => $a->workoutPlan->name]) }}</span>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input wire:model="editDate" :label="__('Date')" type="date" />
                <flux:input wire:model="editNotes" :label="__('Notes')" :placeholder="__('Optional message...')" />
            </div>
            <div class="flex gap-2 justify-end">
                <flux:button size="sm" variant="ghost" wire:click="cancelEdit">{{ __('Cancel') }}</flux:button>
                <flux:button size="sm" variant="primary" wire:click="saveEdit">{{ __('Save') }}</flux:button>
            </div>
        </div>
    @else
        <div class="flex items-center gap-4 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">

            {{-- Date badge --}}
            <div class="shrink-0 w-14 text-center">
                <div class="text-xs font-semibold uppercase {{ $isToday ? 'text-blue-500' : 'text-zinc-400' }}">
                    {{ $isToday ? __('Today') : $a->scheduled_date->format('M') }}
                </div>
                <div class="text-xl font-bold leading-none {{ $isToday ? 'text-blue-600' : 'text-zinc-700 dark:text-zinc-200' }}">
                    {{ $a->scheduled_date->format('j') }}
                </div>
                @if (! $isToday)
                    <div class="text-xs text-zinc-400">{{ $a->scheduled_date->format('Y') }}</div>
                @endif
            </div>

            {{-- Plan name + notes --}}
            <div class="flex-1 min-w-0">
                <div class="font-medium truncate">{{ $a->workoutPlan->name }}</div>
                <div class="flex items-center gap-2 mt-1">
                    @if ($isToday)
                        <flux:badge color="blue" size="sm">{{ __('Today') }}</flux:badge>
                    @elseif ($isFuture)
                        <flux:badge color="zinc" size="sm">{{ $a->scheduled_date->diffForHumans() }}</flux:badge>
                    @endif
                    @if ($a->notes)
                        <span class="text-xs text-zinc-400 truncate">{{ $a->notes }}</span>
                    @endif
                </div>
            </div>

            {{-- Progress --}}
            <div class="shrink-0 flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2">
                    <div class="w-20 h-2 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                        <div class="h-full rounded-full transition-all {{ $isCompleted ? 'bg-green-500' : 'bg-blue-500' }}"
                             style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="text-sm font-semibold w-9 text-right {{ $isCompleted ? 'text-green-600' : 'text-zinc-500' }}">
                        {{ $pct }}%
                    </span>
                </div>
                @if ($isCompleted)
                    <flux:badge color="green">{{ __('Done') }}</flux:badge>
                @endif
            </div>

            {{-- Actions --}}
            <div class="shrink-0 flex items-center gap-1">
                <flux:button size="sm" variant="ghost" icon="pencil"
                    wire:click="startEdit({{ $a->id }})" />
                <flux:button size="sm" variant="ghost" icon="trash"
                    wire:click="deleteAssignment({{ $a->id }})"
                    :wire:confirm="__('Delete this assignment?')" />
                <flux:button size="sm" variant="ghost"
                    :icon="$isExpanded ? 'chevron-up' : 'chevron-down'"
                    wire:click="toggleExpand({{ $a->id }})" />
            </div>
        </div>
    @endif

    {{-- Expanded exercise list --}}
    @if ($isExpanded && ! $isEditing)
        <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-3 flex flex-col gap-2 bg-zinc-50/50 dark:bg-zinc-800/20">
            <div class="text-xs font-semibold uppercase tracking-wide text-zinc-400 mb-1">
                {{ __('Exercises — :count total', ['count' => $items->count()]) }}
            </div>
            @foreach ($items as $item)
                @php
                    $log  = $logs->get($item->id);
                    $done = $log && $log->completed_at;
                @endphp
                <div class="flex items-center gap-3 rounded-lg px-3 py-2
                    {{ $done
                        ? 'bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800'
                        : 'bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700' }}">
                    <div class="shrink-0 size-5 rounded-full flex items-center justify-center
                        {{ $done ? 'bg-green-500' : 'border-2 border-zinc-300 dark:border-zinc-600' }}">
                        @if ($done)
                            <svg class="size-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        @endif
                    </div>
                    <span class="flex-1 text-sm {{ $done ? 'text-zinc-400 line-through' : '' }}">
                        {{ $item->exercise->name }}
                    </span>
                    <span class="text-xs text-zinc-400">{{ $item->sets }}×{{ $item->reps }}</span>
                    @if ($item->exercise->muscle_group)
                        <flux:badge size="sm" color="zinc">{{ $item->exercise->muscle_group }}</flux:badge>
                    @endif
                    @if ($done && $log->completed_at)
                        <span class="text-xs text-zinc-400 hidden sm:inline">
                            {{ $log->completed_at->format('g:ia') }}
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

</div>
