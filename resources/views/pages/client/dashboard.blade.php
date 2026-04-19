<?php

use App\Models\Assignment;
use App\Models\ExerciseLog;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('My Workout')] class extends Component {

    public ?int $activeAssignmentId = null;  // which workout is expanded

    public function mount(): void
    {
        // Auto-open the first incomplete workout today
        $client = auth()->user();
        $first = Assignment::where('client_id', $client->id)
            ->where('scheduled_date', today())
            ->first();
        if ($first) {
            $this->activeAssignmentId = $first->id;
        }
    }

    public function openWorkout(int $id): void
    {
        $this->activeAssignmentId = ($this->activeAssignmentId === $id) ? null : $id;
    }

    public function complete(int $assignmentId, int $planExerciseId): void
    {
        $client = auth()->user();

        Assignment::where('id', $assignmentId)
            ->where('client_id', $client->id)
            ->firstOrFail();

        $log = ExerciseLog::where('assignment_id', $assignmentId)
            ->where('workout_plan_exercise_id', $planExerciseId)
            ->where('client_id', $client->id)
            ->first();

        if ($log) {
            if ($log->completed_at) {
                $log->update(['completed_at' => null]);
            } else {
                $log->update(['completed_at' => now()]);
            }
        } else {
            ExerciseLog::create([
                'assignment_id'            => $assignmentId,
                'workout_plan_exercise_id' => $planExerciseId,
                'client_id'                => $client->id,
                'completed_at'             => now(),
            ]);
        }
    }

    public function render()
    {
        $client = auth()->user();

        $today = Assignment::with([
                'workoutPlan.workoutPlanExercises' => fn($q) => $q->orderBy('sort_order'),
                'workoutPlan.workoutPlanExercises.exercise.media',
                'exerciseLogs',
            ])
            ->where('client_id', $client->id)
            ->where('scheduled_date', today())
            ->get();

        $upcoming = Assignment::with('workoutPlan')
            ->where('client_id', $client->id)
            ->where('scheduled_date', '>', today())
            ->where('scheduled_date', '<=', today()->addDays(7))
            ->orderBy('scheduled_date')
            ->get();

        return view('pages.client.dashboard', compact('today', 'upcoming'));
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6 max-w-2xl mx-auto">

    {{-- Date header --}}
    <div>
        <flux:heading size="xl">{{ now()->format('l') }}</flux:heading>
        <flux:text class="text-zinc-500">{{ now()->format('F j, Y') }}</flux:text>
    </div>

    {{-- Today's workouts --}}
    @forelse ($today as $assignment)
        @php
            $logs      = $assignment->exerciseLogs->keyBy('workout_plan_exercise_id');
            $exercises = $assignment->workoutPlan->workoutPlanExercises;
            $total     = $exercises->count();
            $done      = $logs->filter(fn($l) => $l->completed_at)->count();
            $pct       = $total > 0 ? (int) round($done / $total * 100) : 0;
            $allDone   = $pct === 100;
            $isOpen    = $activeAssignmentId === $assignment->id;
        @endphp

        <div class="rounded-2xl border {{ $allDone ? 'border-green-300 dark:border-green-700 bg-green-50/50 dark:bg-green-950/20' : 'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900' }} overflow-hidden transition-colors">

            {{-- Workout header (always visible, tap to expand) --}}
            <button wire:click="openWorkout({{ $assignment->id }})"
                class="w-full flex items-center gap-4 px-5 py-4 text-left">

                {{-- Progress ring --}}
                <div class="relative shrink-0 size-14">
                    <svg class="size-14 -rotate-90" viewBox="0 0 56 56">
                        <circle cx="28" cy="28" r="24" fill="none"
                            stroke="{{ $allDone ? '#86efac' : '#e4e4e7' }}"
                            stroke-width="5" class="dark:stroke-zinc-700" />
                        <circle cx="28" cy="28" r="24" fill="none"
                            stroke="{{ $allDone ? '#22c55e' : '#3b82f6' }}"
                            stroke-width="5"
                            stroke-linecap="round"
                            stroke-dasharray="{{ round(2 * M_PI * 24, 2) }}"
                            stroke-dashoffset="{{ round(2 * M_PI * 24 * (1 - $pct / 100), 2) }}"
                            class="transition-all duration-500" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        @if ($allDone)
                            <svg class="size-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            <span class="text-xs font-bold text-zinc-700 dark:text-zinc-200">{{ $pct }}%</span>
                        @endif
                    </div>
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-base truncate">{{ $assignment->workoutPlan->name }}</div>
                    <div class="text-sm text-zinc-500 mt-0.5">
                        {{ $done }}/{{ $total }} {{ __('exercises') }}
                        @if ($allDone)
                            · <span class="text-green-600 font-medium">{{ __('Complete!') }}</span>
                        @endif
                    </div>
                    @if ($assignment->notes)
                        <div class="text-xs text-zinc-400 mt-1 truncate">{{ $assignment->notes }}</div>
                    @endif
                </div>

                {{-- Chevron --}}
                <flux:icon name="{{ $isOpen ? 'chevron-up' : 'chevron-down' }}"
                    class="size-5 text-zinc-400 shrink-0 transition-transform" />
            </button>

            {{-- Exercise list (collapsible) --}}
            @if ($isOpen)
                <div class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800 border-t border-zinc-100 dark:border-zinc-800">
                    @foreach ($exercises as $index => $item)
                        @php
                            $log       = $logs->get($item->id);
                            $completed = $log && $log->completed_at;
                            $exercise  = $item->exercise;
                            $images    = $exercise->media->where('type', 'image');
                            $video     = $exercise->media->whereIn('type', ['video_url', 'video'])->first();
                            $hasMedia  = $images->isNotEmpty() || $video;
                        @endphp

                        <div x-data="{ showImages: false, playing: false }"
                            class="flex flex-col {{ $completed ? 'bg-green-50/60 dark:bg-green-950/20' : '' }} transition-colors">

                            {{-- Main exercise row --}}
                            <div class="flex items-start gap-3 px-5 py-3.5">

                                {{-- Complete button --}}
                                <button wire:click="complete({{ $assignment->id }}, {{ $item->id }})"
                                    class="shrink-0 size-7 rounded-full flex items-center justify-center transition-all mt-0.5
                                           {{ $completed
                                               ? 'bg-green-500 shadow-sm shadow-green-300 dark:shadow-green-900 scale-110'
                                               : 'border-2 border-zinc-300 dark:border-zinc-600 hover:border-zinc-400' }}"
                                    title="{{ $completed ? __('Mark incomplete') : __('Mark complete') }}">
                                    @if ($completed)
                                        <svg class="size-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                </button>

                                {{-- Video thumbnail (always visible if available) --}}
                                @if ($video)
                                    <button @click="playing = !playing"
                                        class="relative shrink-0 w-24 h-24 rounded-lg overflow-hidden bg-zinc-900 group/thumb shadow-sm">
                                        @if ($video->thumbnailUrl())
                                            <img src="{{ $video->thumbnailUrl() }}" alt="{{ $exercise->name }}"
                                                class="w-full h-full object-cover transition duration-300 group-hover/thumb:scale-105" />
                                        @else
                                            <div class="w-full h-full flex items-center justify-center bg-zinc-800">
                                                <flux:icon name="film" class="size-8 text-zinc-600" />
                                            </div>
                                        @endif
                                        {{-- Play overlay --}}
                                        <div class="absolute inset-0 bg-black/10 group-hover/thumb:bg-black/30 transition"></div>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="size-10 rounded-full bg-black/50 backdrop-blur-sm flex items-center justify-center
                                                        group-hover/thumb:scale-110 group-hover/thumb:bg-black/70 transition">
                                                <svg class="size-5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M8 5v14l11-7z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </button>
                                @endif

                                {{-- Exercise details --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline gap-2 flex-wrap">
                                        <span class="font-medium {{ $completed ? 'line-through text-zinc-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                            {{ $exercise->name }}
                                        </span>
                                        <span class="text-sm font-semibold text-blue-600 dark:text-blue-400 shrink-0">
                                            @if ($item->sets && $item->reps)
                                                {{ $item->sets }} × {{ $item->reps }} {{ __('reps') }}
                                            @elseif ($item->duration_seconds)
                                                {{ gmdate('i:s', $item->duration_seconds) }}
                                            @endif
                                        </span>
                                    </div>
                                    @if ($exercise->muscle_group)
                                        <span class="text-xs text-zinc-400">{{ $exercise->muscle_group }}</span>
                                    @endif
                                    @if ($item->notes)
                                        <p class="text-xs text-zinc-500 mt-0.5">{{ $item->notes }}</p>
                                    @endif

                                    {{-- Images button --}}
                                    @if ($images->isNotEmpty())
                                        <button @click="showImages = !showImages"
                                            class="flex items-center gap-1.5 text-xs font-medium rounded-lg px-2 py-1 mt-1.5 transition
                                                   {{ showImages
                                                       ? 'bg-zinc-700 text-white dark:bg-zinc-200 dark:text-zinc-900'
                                                       : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
                                            <flux:icon name="photo" class="size-3.5" />
                                            <span>{{ $images->count() }} {{ $images->count() === 1 ? __('image') : __('images') }}</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Full-screen video player (when playing) --}}
                            @if ($video)
                                <div x-show="playing" x-cloak class="px-5 pb-4">
                                    <div class="relative w-full rounded-xl overflow-hidden bg-black">
                                        @if ($video->isYoutube())
                                            <div class="relative w-full bg-black" style="padding-top:56.25%">
                                                <iframe src="{{ $video->playUrl() }}"
                                                    class="absolute inset-0 w-full h-full"
                                                    frameborder="0"
                                                    allow="clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                    allowfullscreen></iframe>
                                            </div>
                                        @else
                                            <video src="{{ $video->playUrl() }}" controls autoplay
                                                class="w-full rounded-xl bg-black max-h-96"></video>
                                        @endif
                                        {{-- Close button --}}
                                        <button @click="playing = false"
                                            class="absolute top-2 right-2 size-8 rounded-full bg-black/60 text-white flex items-center justify-center text-lg hover:bg-black/80 transition z-10"
                                            title="{{ __('Close video') }}">×</button>
                                    </div>
                                </div>
                            @endif

                            {{-- Images --}}
                            @if ($images->isNotEmpty())
                                <div x-show="showImages" x-cloak class="flex gap-2 flex-wrap px-5 pb-4">
                                    @foreach ($images as $img)
                                        <img src="{{ $img->publicUrl() }}" alt="{{ $exercise->name }}"
                                            class="h-28 rounded-xl object-cover border border-zinc-200 dark:border-zinc-700" />
                                    @endforeach
                                </div>
                            @endif

                            {{-- Exercise description --}}
                            @if ($exercise->description && !$completed)
                                <div class="mx-5 mb-3 rounded-lg bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-500 leading-relaxed">
                                    {{ $exercise->description }}
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- All done banner --}}
                    @if ($allDone)
                        <div class="flex items-center justify-center gap-3 py-5 bg-green-50 dark:bg-green-950/30">
                            <svg class="size-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-semibold text-green-700 dark:text-green-400">{{ __('Workout complete — great work!') }}</span>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @empty
        {{-- Rest day --}}
        <div class="flex flex-col items-center justify-center gap-4 py-20 text-center">
            <div class="flex size-20 items-center justify-center rounded-3xl bg-zinc-100 dark:bg-zinc-800">
                <svg class="size-10 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636" />
                </svg>
            </div>
            <div>
                <flux:heading>{{ __('Rest day') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500">{{ __('No workout scheduled for today. Recover well!') }}</flux:text>
            </div>
        </div>
    @endforelse

    {{-- Upcoming --}}
    @if ($upcoming->isNotEmpty())
        <div class="flex flex-col gap-3">
            <flux:heading size="lg">{{ __('Coming Up') }}</flux:heading>
            @foreach ($upcoming as $a)
                <div class="flex items-center gap-4 rounded-xl border border-zinc-200 dark:border-zinc-700 px-4 py-3">
                    {{-- Day block --}}
                    <div class="flex flex-col items-center justify-center size-12 rounded-xl bg-zinc-100 dark:bg-zinc-800 shrink-0">
                        <span class="text-xs text-zinc-500 leading-none">{{ $a->scheduled_date->format('D') }}</span>
                        <span class="text-lg font-bold text-zinc-800 dark:text-zinc-100 leading-tight">{{ $a->scheduled_date->format('j') }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium truncate">{{ $a->workoutPlan->name }}</div>
                        <div class="text-sm text-zinc-500">{{ $a->scheduled_date->diffForHumans() }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
