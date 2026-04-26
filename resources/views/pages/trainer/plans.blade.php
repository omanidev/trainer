<?php

use App\Models\Exercise;
use App\Models\WorkoutPlan;
use App\Models\WorkoutPlanExercise;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Workout Plans')] class extends Component {

    // Plan form
    public string $planName  = '';
    public string $planNotes = '';
    public ?int   $editingPlanId = null;

    // Which plan's exercises we're managing
    public ?int $managingPlanId = null;

    // Add-exercise form inside the manage modal
    public int    $exerciseId    = 0;
    public int    $sets          = 3;
    public int    $reps          = 10;
    public string $exerciseNotes = '';

    // Inline-edit of an existing plan exercise
    public ?int $editingItemId   = null;
    public int  $editingSets     = 3;
    public int  $editingReps     = 10;
    public string $editingNotes  = '';

    // ── Plan CRUD ──────────────────────────────────────────

    public function openNewPlan(): void
    {
        $this->reset(['planName', 'planNotes', 'editingPlanId']);
        Flux::modal('plan-form')->show();
    }

    public function editPlan(int $id): void
    {
        $plan = WorkoutPlan::where('id', $id)->where('trainer_id', auth()->id())->firstOrFail();
        $this->editingPlanId = $plan->id;
        $this->planName      = $plan->name;
        $this->planNotes     = $plan->notes ?? '';
        Flux::modal('plan-form')->show();
    }

    public function savePlan(): void
    {
        $this->validate([
            'planName'  => 'required|string|max:255',
            'planNotes' => 'nullable|string',
        ]);

        if ($this->editingPlanId) {
            WorkoutPlan::where('id', $this->editingPlanId)
                ->where('trainer_id', auth()->id())
                ->update(['name' => $this->planName, 'notes' => $this->planNotes ?: null]);
            Flux::toast(__('Plan updated.'), variant: 'success');
        } else {
            WorkoutPlan::create([
                'trainer_id' => auth()->id(),
                'name'       => $this->planName,
                'notes'      => $this->planNotes ?: null,
            ]);
            Flux::toast(__('Plan created.'), variant: 'success');
        }

        $this->reset(['planName', 'planNotes', 'editingPlanId']);
        Flux::modal('plan-form')->close();
    }

    public function deletePlan(int $id): void
    {
        WorkoutPlan::where('id', $id)->where('trainer_id', auth()->id())->delete();
        Flux::toast(__('Plan deleted.'), variant: 'success');
    }

    // ── Manage exercises modal ──────────────────────────────

    public function managePlan(int $id): void
    {
        $this->managingPlanId = $id;
        $this->reset(['exerciseId', 'sets', 'reps', 'exerciseNotes', 'editingItemId']);
        $this->sets = 3;
        $this->reps = 10;
        Flux::modal('manage-exercises')->show();
    }

    public function addExerciseToPlan(): void
    {
        $this->validate([
            'exerciseId' => 'required|integer|min:1',
            'sets'       => 'required|integer|min:1|max:99',
            'reps'       => 'required|integer|min:1|max:999',
        ]);

        // Verify exercise belongs to this trainer
        Exercise::where('id', $this->exerciseId)
            ->where('trainer_id', auth()->id())
            ->firstOrFail();

        /** @var \App\Models\WorkoutPlan $plan */
        $plan      = WorkoutPlan::where('id', $this->managingPlanId)->where('trainer_id', auth()->id())->firstOrFail();
        $nextOrder = $plan->workoutPlanExercises()->max('sort_order') + 1;

        WorkoutPlanExercise::create([
            'workout_plan_id' => $plan->id,
            'exercise_id'     => $this->exerciseId,
            'sets'            => $this->sets,
            'reps'            => $this->reps,
            'sort_order'      => $nextOrder,
            'notes'           => $this->exerciseNotes ?: null,
        ]);

        $this->reset(['exerciseId', 'exerciseNotes']);
        $this->sets = 3;
        $this->reps = 10;
        Flux::toast(__('Exercise added to plan.'), variant: 'success');
    }

    public function startEditItem(int $itemId): void
    {
        $item = WorkoutPlanExercise::findOrFail($itemId);
        $this->editingItemId  = $item->id;
        $this->editingSets    = $item->sets;
        $this->editingReps    = $item->reps;
        $this->editingNotes   = $item->notes ?? '';
    }

    public function saveItem(): void
    {
        $this->validate([
            'editingSets' => 'required|integer|min:1|max:99',
            'editingReps' => 'required|integer|min:1|max:999',
        ]);

        WorkoutPlanExercise::where('id', $this->editingItemId)
            ->whereHas('workoutPlan', fn($q) => $q->where('trainer_id', auth()->id()))
            ->update([
                'sets'  => $this->editingSets,
                'reps'  => $this->editingReps,
                'notes' => $this->editingNotes ?: null,
            ]);

        $this->reset(['editingItemId', 'editingSets', 'editingReps', 'editingNotes']);
        Flux::toast(__('Exercise updated.'), variant: 'success');
    }

    public function cancelEditItem(): void
    {
        $this->reset(['editingItemId', 'editingSets', 'editingReps', 'editingNotes']);
    }

    public function moveItem(int $itemId, string $direction): void
    {
        $item = WorkoutPlanExercise::where('id', $itemId)
            ->whereHas('workoutPlan', fn($q) => $q->where('trainer_id', auth()->id()))
            ->firstOrFail();

        $sibling = WorkoutPlanExercise::where('workout_plan_id', $item->workout_plan_id)
            ->when($direction === 'up',   fn($q) => $q->where('sort_order', '<', $item->sort_order)->orderByDesc('sort_order'))
            ->when($direction === 'down', fn($q) => $q->where('sort_order', '>', $item->sort_order)->orderBy('sort_order'))
            ->first();

        if ($sibling) {
            [$item->sort_order, $sibling->sort_order] = [$sibling->sort_order, $item->sort_order];
            $item->save();
            $sibling->save();
        }
    }

    public function removeItem(int $itemId): void
    {
        WorkoutPlanExercise::where('id', $itemId)
            ->whereHas('workoutPlan', fn($q) => $q->where('trainer_id', auth()->id()))
            ->delete();
    }

    // ── Render ─────────────────────────────────────────────

    public function render()
    {
        $managingPlan = $this->managingPlanId
            ? WorkoutPlan::with('workoutPlanExercises.exercise')
                ->where('id', $this->managingPlanId)
                ->where('trainer_id', auth()->id())
                ->first()
            : null;

        /** @var \App\Models\User $trainer */
        $trainer = auth()->user();

        return view('pages.trainer.plans', [
            'plans'        => $trainer->workoutPlans()
                ->with('workoutPlanExercises.exercise')
                ->withCount('workoutPlanExercises')
                ->latest()
                ->get(),
            'exercises'    => $trainer->exercises()->orderBy('name')->get(),
            'managingPlan' => $managingPlan,
        ]);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Workout Plans') }}</flux:heading>
            <flux:text class="text-zinc-500 text-sm mt-0.5">{{ $plans->count() }} {{ $plans->count() === 1 ? __('plan') : __('plans') }}</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openNewPlan">{{ __('New Plan') }}</flux:button>
    </div>

    {{-- Create / Edit Plan modal --}}
    <flux:modal name="plan-form" class="md:w-lg">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">{{ $editingPlanId ? __('Edit Plan') : __('New Workout Plan') }}</flux:heading>
                <flux:text class="text-zinc-500 text-sm mt-1">
                    {{ __('Give the plan a name, then add exercises to it.') }}
                </flux:text>
            </div>

            <flux:input wire:model="planName" :label="__('Plan Name')" :placeholder="__('e.g. Upper Body Strength')" autofocus />
            <flux:textarea wire:model="planNotes" :label="__('Description')" :placeholder="__('What\'s the goal of this plan? Any notes for clients…')" rows="3" />

            <div class="flex justify-end gap-2 pt-1">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="savePlan">
                    {{ $editingPlanId ? __('Save Changes') : __('Create Plan') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Manage Exercises modal --}}
    <flux:modal name="manage-exercises" class="md:w-2xl">
        @if ($managingPlan)
            <div class="flex flex-col gap-5">

                <div>
                    <flux:heading size="lg">{{ $managingPlan->name }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 mt-1">
                        {{ __('Drag to reorder — use the arrows to change exercise order.') }}
                    </flux:text>
                </div>

                {{-- Existing exercises --}}
                @if ($managingPlan->workoutPlanExercises->isEmpty())
                    <div class="flex flex-col items-center gap-2 py-8 text-center rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700">
                        <flux:icon name="list-bullet" class="size-8 text-zinc-400" />
                        <flux:text class="text-zinc-500">{{ __('No exercises yet — add one below.') }}</flux:text>
                    </div>
                @else
                    <div class="flex flex-col gap-2">
                        @foreach ($managingPlan->workoutPlanExercises as $item)
                            @if ($editingItemId === $item->id)
                                {{-- Inline edit row --}}
                                <div class="rounded-xl border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-950/30 p-3 flex flex-col gap-3">
                                    <div class="font-medium text-sm text-blue-700 dark:text-blue-400">
                                        {{ __('Editing: :name', ['name' => $item->exercise->name]) }}
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <flux:input wire:model="editingSets" :label="__('Sets')" type="number" min="1" max="99" />
                                        <flux:input wire:model="editingReps" :label="__('Reps')" type="number" min="1" max="999" />
                                        <flux:input wire:model="editingNotes" :label="__('Notes')" :placeholder="__('Optional')" />
                                    </div>
                                    <div class="flex gap-2 justify-end">
                                        <flux:button size="sm" variant="ghost" wire:click="cancelEditItem">{{ __('Cancel') }}</flux:button>
                                        <flux:button size="sm" variant="primary" wire:click="saveItem">{{ __('Save') }}</flux:button>
                                    </div>
                                </div>
                            @else
                                {{-- Normal row --}}
                                <div class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 px-3 py-2.5 bg-white dark:bg-zinc-900">
                                    {{-- Order buttons --}}
                                    <div class="flex flex-col gap-0.5 shrink-0">
                                        <button wire:click="moveItem({{ $item->id }}, 'up')"
                                            class="rounded p-0.5 text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 transition">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                            </svg>
                                        </button>
                                        <button wire:click="moveItem({{ $item->id }}, 'down')"
                                            class="rounded p-0.5 text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 transition">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Exercise info --}}
                                    <div class="flex-1 min-w-0">
                                        <span class="font-medium text-sm">{{ $item->exercise->name }}</span>
                                        @if ($item->exercise->muscle_group)
                                            <flux:badge size="sm" color="zinc" class="ml-2">{{ __($item->exercise->muscle_group) }}</flux:badge>
                                        @endif
                                        @if ($item->notes)
                                            <p class="text-xs text-zinc-400 mt-0.5">{{ $item->notes }}</p>
                                        @endif
                                    </div>

                                    {{-- Sets × Reps --}}
                                    <div class="shrink-0 text-sm font-semibold text-zinc-600 dark:text-zinc-300 w-16 text-center">
                                        {{ $item->sets }}×{{ $item->reps }}
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex gap-1 shrink-0">
                                        <flux:button size="sm" variant="ghost" icon="pencil"
                                            wire:click="startEditItem({{ $item->id }})" />
                                        <flux:button size="sm" variant="ghost" icon="trash"
                                            wire:click="removeItem({{ $item->id }})"
                                            :wire:confirm="__('Remove this exercise from the plan?')" />
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                <flux:separator />

                {{-- Add exercise from library --}}
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">{{ __('Add from Library') }}</flux:heading>
                        <a href="{{ route('trainer.exercises') }}" wire:navigate
                            class="text-xs text-blue-500 hover:underline flex items-center gap-1">
                            <flux:icon name="arrow-top-right-on-square" class="size-3.5" />
                            {{ __('Manage library') }}
                        </a>
                    </div>

                    @if ($exercises->isEmpty())
                        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 px-4 py-6 text-center flex flex-col items-center gap-2">
                            <flux:icon name="bolt" class="size-7 text-zinc-400" />
                            <flux:text class="text-sm text-zinc-500">
                                {{ __('Your exercise library is empty.') }}<br>
                                <a href="{{ route('trainer.exercises') }}" class="text-blue-500 hover:underline" wire:navigate>{{ __('Create exercises first →') }}</a>
                            </flux:text>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="col-span-2 sm:col-span-2">
                                <flux:select wire:model="exerciseId" :label="__('Exercise from library')">
                                    <flux:select.option value="0">{{ __('— Pick exercise —') }}</flux:select.option>
                                    @foreach ($exercises->groupBy('muscle_group') as $group => $groupExercises)
                                        @if ($group)
                                            <flux:select.option disabled>── {{ __($group) }} ──</flux:select.option>
                                        @endif
                                        @foreach ($groupExercises as $ex)
                                            <flux:select.option value="{{ $ex->id }}">{{ $ex->name }}</flux:select.option>
                                        @endforeach
                                    @endforeach
                                </flux:select>
                            </div>
                            <flux:input wire:model="sets" :label="__('Sets')" type="number" min="1" max="99" />
                            <flux:input wire:model="reps" :label="__('Reps')" type="number" min="1" max="999" />
                            <div class="col-span-2 sm:col-span-4">
                                <flux:input wire:model="exerciseNotes" :label="__('Notes for client')" :placeholder="__('e.g. Use moderate weight, full range of motion')" />
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <flux:button variant="primary" icon="plus" wire:click="addExerciseToPlan">
                                {{ __('Add to Plan') }}
                            </flux:button>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end pt-1">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Done') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Empty state --}}
    @if ($plans->isEmpty())
        <flux:card class="flex flex-col items-center justify-center gap-4 py-20 text-center">
            <div class="flex size-16 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="clipboard-document-list" class="size-8 text-zinc-400" />
            </div>
            <div>
                <flux:heading>{{ __('No workout plans yet') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Create a plan and fill it with exercises from your library.') }}</flux:text>
            </div>
            <flux:button variant="primary" icon="plus" wire:click="openNewPlan">{{ __('Create your first plan') }}</flux:button>
        </flux:card>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($plans as $plan)
                <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5 transition-all duration-200 hover:shadow-lg hover:scale-[1.02] hover:border-blue-300 dark:hover:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-950/30">

                    {{-- Plan header --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold truncate dark:text-zinc-100">{{ $plan->name }}</div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                                {{ $plan->workout_plan_exercises_count }} {{ $plan->workout_plan_exercises_count === 1 ? __('exercise') : __('exercises') }}
                            </div>
                        </div>
                        <div class="flex gap-1 shrink-0">
                            <flux:button size="sm" variant="ghost" icon="pencil"
                                wire:click="editPlan({{ $plan->id }})"
                                class="hover:bg-blue-100 dark:hover:bg-blue-950/50 hover:text-blue-600 dark:hover:text-blue-400" />
                            <flux:button size="sm" variant="ghost" icon="trash"
                                wire:click="deletePlan({{ $plan->id }})"
                                :wire:confirm="__('Delete \':name\'? This will also remove all assignments using it.', ['name' => $plan->name])"
                                class="hover:bg-red-100 dark:hover:bg-red-950/50 hover:text-red-600 dark:hover:text-red-400" />
                        </div>
                    </div>

                    @if ($plan->notes)
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2 -mt-2">{{ $plan->notes }}</p>
                    @endif

                    {{-- Exercise preview list --}}
                    @if ($plan->workoutPlanExercises->isNotEmpty())
                        <div class="flex flex-col gap-1.5">
                            @foreach ($plan->workoutPlanExercises->take(4) as $item)
                                <div class="flex items-center gap-2 text-sm">
                                    <div class="size-1.5 rounded-full bg-blue-400 dark:bg-blue-500 shrink-0"></div>
                                    <span class="flex-1 truncate text-zinc-700 dark:text-zinc-300">{{ $item->exercise->name }}</span>
                                    <span class="text-zinc-400 dark:text-zinc-500 shrink-0">{{ $item->sets }}×{{ $item->reps }}</span>
                                </div>
                            @endforeach
                            @if ($plan->workout_plan_exercises_count > 4)
                                <div class="text-xs text-zinc-400 pl-3.5">
                                    +{{ $plan->workout_plan_exercises_count - 4 }} {{ __('more') }}
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-sm text-zinc-400 italic">{{ __('No exercises added yet.') }}</div>
                    @endif

                    {{-- Action --}}
                    <flux:button size="sm" variant="outline" icon="list-bullet"
                        wire:click="managePlan({{ $plan->id }})" class="mt-auto">
                        {{ __('Manage Exercises') }}
                    </flux:button>
                </div>
            @endforeach
        </div>
    @endif

</div>
