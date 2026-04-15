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

        return view('pages.trainer.client-detail', compact(
            'upcoming', 'past', 'totalAssignments', 'totalCompleted', 'avgCompletion'
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

    {{-- Upcoming --}}
    @if ($upcoming->isNotEmpty())
        <div class="flex flex-col gap-3">
            <flux:heading size="lg">{{ __('Upcoming') }}</flux:heading>
            @foreach ($upcoming->sortBy('scheduled_date') as $a)
                @include('pages.trainer.partials.assignment-row', ['a' => $a])
            @endforeach
        </div>
    @endif

    {{-- Past --}}
    <div class="flex flex-col gap-3">
        <flux:heading size="lg">{{ __('Past Workouts') }}</flux:heading>
        @forelse ($past as $a)
            @include('pages.trainer.partials.assignment-row', ['a' => $a])
        @empty
            <flux:text class="text-zinc-500">{{ __('No past workouts yet.') }}</flux:text>
        @endforelse
    </div>

</div>
