<?php

use App\Models\Exercise;
use App\Models\ExerciseMedia;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] #[Title('Exercise Library')] class extends Component {

    use WithFileUploads;

    // Exercise fields
    public string $name         = '';
    public string $description  = '';
    public string $muscle_group = '';
    public ?int   $editingId    = null;

    // Filters
    public string $filterGroup = '';
    public string $search      = '';

    // Image uploads
    public array $newImages = [];

    // Video — mode: 'url' or 'upload'
    public string  $videoMode       = 'url';
    public string  $videoUrl        = '';
    public         $uploadedVideo   = null;
    public ?int    $existingVideoId = null; // ID of current video when editing


    const MUSCLE_GROUPS = ['Chest', 'Back', 'Shoulders', 'Arms', 'Core', 'Legs', 'Cardio', 'Full Body'];

    // ── CRUD ─────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('exercise-form')->show();
    }

    public function edit(int $id): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        /** @var \App\Models\Exercise $exercise */
        $exercise = Exercise::where('id', $id)
            ->where('trainer_id', $user->id)
            ->firstOrFail();

        $this->editingId    = $exercise->id;
        $this->name         = $exercise->name;
        $this->description  = $exercise->description ?? '';
        $this->muscle_group = $exercise->muscle_group ?? '';
        $this->newImages    = [];

        $videoMedia = $exercise->media()->whereIn('type', ['video_url', 'video'])->first();

        if ($videoMedia) {
            $this->existingVideoId = $videoMedia->id;
            $this->videoMode       = $videoMedia->isUploadedVideo() ? 'upload' : 'url';
            $this->videoUrl        = $videoMedia->url ?? '';
        } else {
            $this->existingVideoId = null;
            $this->videoMode       = 'url';
            $this->videoUrl        = '';
        }

        Flux::modal('exercise-form')->show();
    }

    public function save(): void
    {
        $rules = [
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'muscle_group' => 'nullable|string|max:100',
            'newImages.*'  => 'nullable|image|max:4096',
        ];

        if ($this->videoMode === 'url') {
            $rules['videoUrl'] = 'nullable|url|max:500';
        } else {
            $rules['uploadedVideo'] = 'nullable|file|mimetypes:video/mp4,video/webm,video/ogg,video/quicktime|max:20480';
        }

        $this->validate($rules);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($this->editingId) {
            /** @var \App\Models\Exercise $exercise */
            $exercise = Exercise::where('id', $this->editingId)
                ->where('trainer_id', $user->id)
                ->firstOrFail();

            $exercise->update([
                'name'         => $this->name,
                'description'  => $this->description ?: null,
                'muscle_group' => $this->muscle_group ?: null,
            ]);
            Flux::toast(__('Exercise updated.'), variant: 'success');
        } else {
            /** @var \App\Models\Exercise $exercise */
            $exercise = Exercise::create([
                'trainer_id'   => $user->id,
                'name'         => $this->name,
                'description'  => $this->description ?: null,
                'muscle_group' => $this->muscle_group ?: null,
            ]);
            Flux::toast(__('Exercise added.'), variant: 'success');
        }

        // Handle video
        $this->saveVideo($exercise);

        // Store uploaded images
        $nextOrder = $exercise->media()->where('type', 'image')->max('sort_order') + 1;
        foreach ($this->newImages as $i => $image) {
            $path = $image->store('exercises', 'public');
            $exercise->media()->create([
                'type'       => 'image',
                'path'       => $path,
                'sort_order' => $nextOrder + $i,
            ]);
        }

        $this->resetForm();
        Flux::modal('exercise-form')->close();
    }

    private function saveVideo(Exercise $exercise): void
    {
        if ($this->videoMode === 'url') {
            // Always replace video with the new URL (or remove if empty)
            $exercise->media()->whereIn('type', ['video_url', 'video'])->each(function ($m) {
                if ($m->path) Storage::disk('public')->delete($m->path);
                $m->delete();
            });
            if ($this->videoUrl) {
                $exercise->media()->create([
                    'type'       => 'video_url',
                    'url'        => $this->videoUrl,
                    'sort_order' => 0,
                ]);
            }
        } elseif ($this->videoMode === 'upload' && $this->uploadedVideo) {
            // New file selected — delete old, store new
            $exercise->media()->whereIn('type', ['video_url', 'video'])->each(function ($m) {
                if ($m->path) Storage::disk('public')->delete($m->path);
                $m->delete();
            });
            $path = $this->uploadedVideo->store('exercises/videos', 'public');
            $exercise->media()->create([
                'type'       => 'video',
                'path'       => $path,
                'sort_order' => 0,
            ]);
        }
        // Upload mode with no new file = keep existing video unchanged
    }

    public function deleteMedia(int $mediaId): void
    {
        /** @var \App\Models\User $user */
        $user  = auth()->user();
        $media = ExerciseMedia::whereHas('exercise', fn($q) => $q->where('trainer_id', $user->id))
            ->findOrFail($mediaId);

        if ($media->path) {
            Storage::disk('public')->delete($media->path);
        }
        $media->delete();

        // Clear the existingVideoId if it was the video being deleted
        if ($this->existingVideoId === $mediaId) {
            $this->existingVideoId = null;
            $this->videoUrl        = '';
        }
    }

    public function delete(int $id): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        /** @var \App\Models\Exercise $exercise */
        $exercise = Exercise::where('id', $id)->where('trainer_id', $user->id)->firstOrFail();

        foreach ($exercise->media()->whereIn('type', ['image', 'video'])->get() as $m) {
            if ($m->path) Storage::disk('public')->delete($m->path);
        }
        $exercise->media()->delete();
        $exercise->delete();
        Flux::toast(__('Exercise deleted.'), variant: 'success');
    }

    // ── Filters ───────────────────────────────────────────────

    public function setFilter(string $group): void
    {
        $this->filterGroup = $group;
    }

    // ── Helpers ───────────────────────────────────────────────

    private function resetForm(): void
    {
        $this->reset(['name', 'description', 'muscle_group', 'editingId',
                      'videoUrl', 'newImages', 'uploadedVideo', 'existingVideoId']);
        $this->videoMode = 'url';
    }

    // ── Render ────────────────────────────────────────────────

    public function render()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $exercises = $user->exercises()
            ->with('media')
            ->withCount('workoutPlanExercises')
            ->when($this->filterGroup, fn($q) => $q->where('muscle_group', $this->filterGroup))
            ->when($this->search,      fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->latest()
            ->get();

        $groupCounts = $user->exercises()
            ->selectRaw('muscle_group, count(*) as total')
            ->whereNotNull('muscle_group')
            ->groupBy('muscle_group')
            ->pluck('total', 'muscle_group');

        $editingMedia = $this->editingId
            ? Exercise::find($this->editingId)?->media()->where('type', 'image')->get() ?? collect()
            : collect();

        return view('pages.trainer.exercises', [
            'exercises'    => $exercises,
            'muscleGroups' => self::MUSCLE_GROUPS,
            'groupCounts'  => $groupCounts,
            'totalCount'   => $user->exercises()->count(),
            'editingMedia' => $editingMedia,
        ]);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- ── Header ───────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Exercise Library') }}</flux:heading>
            <flux:text class="text-zinc-500 text-sm mt-0.5">{{ $totalCount }} {{ $totalCount === 1 ? __('exercise') : __('exercises') }} {{ __('total') }}</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreate">{{ __('New Exercise') }}</flux:button>
    </div>

    {{-- ── Search + filter ─────────────────────────────────── --}}
    @if ($totalCount > 0)
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="w-full sm:w-64">
                <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search exercises…')" icon="magnifying-glass" clearable />
            </div>
            <div class="flex flex-wrap gap-2">
                <button wire:click="setFilter('')"
                    class="rounded-full px-3 py-1 text-sm font-medium transition
                           {{ $filterGroup === '' ? 'bg-zinc-800 text-white dark:bg-zinc-200 dark:text-zinc-900' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' }}">
                    {{ __('All') }} ({{ $totalCount }})
                </button>
                @foreach ($muscleGroups as $group)
                    @if (($groupCounts[$group] ?? 0) > 0)
                        <button wire:click="setFilter('{{ $group }}')"
                            class="rounded-full px-3 py-1 text-sm font-medium transition
                                   {{ $filterGroup === $group ? 'bg-zinc-800 text-white dark:bg-zinc-200 dark:text-zinc-900' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' }}">
                            {{ __($group) }} ({{ $groupCounts[$group] }})
                        </button>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── Create / Edit modal ─────────────────────────────── --}}
    <flux:modal name="exercise-form" class="md:w-2xl">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? __('Edit Exercise') : __('New Exercise') }}</flux:heading>
                <flux:text class="text-zinc-500 text-sm mt-1">
                    {{ $editingId ? __('Update the details for this exercise.') : __('Add an exercise to your library.') }}
                </flux:text>
            </div>

            <flux:input wire:model="name" :label="__('Exercise Name')" :placeholder="__('e.g. Barbell Squat')" autofocus />

            <flux:select wire:model="muscle_group" :label="__('Muscle Group')">
                <flux:select.option value="">{{ __('— No group —') }}</flux:select.option>
                @foreach ($muscleGroups as $group)
                    <flux:select.option value="{{ $group }}">{{ __($group) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea wire:model="description" :label="__('Instructions / Notes')"
                :placeholder="__('Describe form cues, equipment needed, or modifications…')" rows="3" />

            {{-- ── Video section ──────────────────────────────── --}}
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <flux:label>{{ __('Video (optional)') }}</flux:label>
                    @error('uploadedVideo')
                        <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                    @enderror
                    {{-- Mode toggle --}}
                    <div class="flex rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden text-xs">
                        <button type="button" wire:click="$set('videoMode', 'url')"
                            class="px-3 py-1.5 transition {{ $videoMode === 'url'
                                ? 'bg-zinc-800 text-white dark:bg-zinc-200 dark:text-zinc-900'
                                : 'text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                            {{ __('Link URL') }}
                        </button>
                        <button type="button" wire:click="$set('videoMode', 'upload')"
                            class="px-3 py-1.5 transition {{ $videoMode === 'upload'
                                ? 'bg-zinc-800 text-white dark:bg-zinc-200 dark:text-zinc-900'
                                : 'text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                            {{ __('Upload') }}
                        </button>
                    </div>
                </div>

                @if ($videoMode === 'url')
                    <flux:input wire:model="videoUrl"
                        :placeholder="__('https://youtube.com/watch?v=… or any video URL')"
                        icon="link" />
                    @error('videoUrl')
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                    @if ($videoUrl && str_contains($videoUrl, 'youtu'))
                        <p class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1 mt-1">
                            <flux:icon name="check-circle" class="size-3.5" /> {{ __('YouTube link detected — thumbnail will be shown automatically.') }}
                        </p>
                    @endif
                @else
                    {{-- Existing uploaded video (edit mode) --}}
                    @if ($existingVideoId)
                        @php $existingVideo = \App\Models\ExerciseMedia::find($existingVideoId); @endphp
                        @if ($existingVideo && $existingVideo->isUploadedVideo())
                            <div class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-3">
                                <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 shrink-0">
                                    <flux:icon name="film" class="size-5 text-zinc-500" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium">{{ __('Current video uploaded') }}</p>
                                    <p class="text-xs text-zinc-500">{{ __('Upload a new file below to replace it') }}</p>
                                </div>
                                <button wire:click="deleteMedia({{ $existingVideoId }})"
                                    class="text-red-500 hover:text-red-700 transition" title="{{ __('Remove video') }}">
                                    <flux:icon name="trash" class="size-4" />
                                </button>
                            </div>
                        @endif
                    @endif

                    <div x-data="{
                        fileSize: null,
                        fileName: null,
                        maxSize: 20 * 1024 * 1024,
                        formatSize(bytes) {
                            if (!bytes) return '';
                            const mb = (bytes / 1024 / 1024).toFixed(2);
                            return mb + ' MB';
                        },
                        checkFile(event) {
                            const file = event.target.files[0];
                            if (file) {
                                this.fileSize = file.size;
                                this.fileName = file.name;
                            } else {
                                this.fileSize = null;
                                this.fileName = null;
                            }
                        }
                    }">
                        <input type="file"
                            wire:model="uploadedVideo"
                            accept="video/mp4,video/webm,video/ogg,video/quicktime"
                            @change="checkFile($event)"
                            class="block w-full text-sm text-zinc-500 file:mr-3 file:rounded-lg file:border-0
                                   file:bg-zinc-100 file:px-3 file:py-1.5 file:text-sm file:font-medium
                                   dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:text-zinc-400" />

                        {{-- File info & warnings --}}
                        <div class="mt-2 space-y-1">
                            <p class="text-xs text-zinc-400">{{ __('MP4, WebM or MOV · max 20 MB') }}</p>

                            <template x-if="fileName">
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="text-zinc-600 dark:text-zinc-400" x-text="fileName"></span>
                                    <span class="font-medium"
                                        :class="fileSize > maxSize ? 'text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-400'"
                                        x-text="formatSize(fileSize)"></span>
                                </div>
                            </template>

                            <template x-if="fileSize && fileSize > maxSize">
                                <div class="flex items-center gap-1.5 text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/30 px-2 py-1.5 rounded-lg">
                                    <flux:icon name="exclamation-triangle" class="size-3.5" />
                                    <span>{{ __('File is too large. Maximum size is 20 MB.') }}</span>
                                </div>
                            </template>
                        </div>

                        {{-- Upload progress --}}
                        <div wire:loading wire:target="uploadedVideo" class="mt-2">
                            <div class="flex items-center gap-2 text-xs text-blue-600 dark:text-blue-400">
                                <svg class="animate-spin size-3.5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ __('Uploading video...') }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ── Images section ─────────────────────────────── --}}
            <div class="flex flex-col gap-3">
                <flux:label>{{ $editingId ? __('Images') : __('Images (optional)') }}</flux:label>

                {{-- Existing images --}}
                @if ($editingMedia->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($editingMedia as $m)
                            <div class="relative group/img">
                                <img src="{{ $m->publicUrl() }}" alt=""
                                    class="h-20 w-20 rounded-xl object-cover border border-zinc-200 dark:border-zinc-700" />
                                <button wire:click="deleteMedia({{ $m->id }})"
                                    class="absolute -top-1.5 -right-1.5 hidden group-hover/img:flex items-center justify-center
                                           size-5 rounded-full bg-red-500 text-white text-xs">×</button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div>
                    <input type="file" wire:model="newImages" multiple accept="image/*"
                        class="block w-full text-sm text-zinc-500 file:mr-3 file:rounded-lg file:border-0
                               file:bg-zinc-100 file:px-3 file:py-1.5 file:text-sm file:font-medium
                               dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:text-zinc-400" />
                    <p class="text-xs text-zinc-400 mt-1">{{ __('PNG, JPG or GIF · max 4 MB each') }}</p>
                </div>

                @if (count($newImages) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach ($newImages as $img)
                            <img src="{{ $img->temporaryUrl() }}" alt="preview"
                                class="h-16 w-16 rounded-xl object-cover border border-zinc-200 dark:border-zinc-700" />
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-2 pt-1">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
                    {{ $editingId ? __('Save Changes') : __('Add Exercise') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- ── Empty state ──────────────────────────────────────── --}}
    @if ($totalCount === 0)
        <flux:card class="flex flex-col items-center justify-center gap-4 py-20 text-center">
            <div class="flex size-16 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="bolt" class="size-8 text-zinc-400" />
            </div>
            <div>
                <flux:heading>{{ __('No exercises yet') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Build your library — exercises are reused across all your workout plans.') }}</flux:text>
            </div>
            <flux:button variant="primary" icon="plus" wire:click="openCreate">{{ __('Add your first exercise') }}</flux:button>
        </flux:card>

    @elseif ($exercises->isEmpty())
        <flux:card class="flex flex-col items-center justify-center gap-3 py-16 text-center">
            <flux:icon name="magnifying-glass" class="size-10 text-zinc-400" />
            <flux:heading>{{ __('No results') }}</flux:heading>
            <flux:text>{{ __('Try a different search or filter.') }}</flux:text>
            <flux:button variant="ghost" wire:click="$set('search', ''); $set('filterGroup', '')">{{ __('Clear filters') }}</flux:button>
        </flux:card>

    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($exercises as $exercise)
                @php
                    $images     = $exercise->media->where('type', 'image');
                    $videoMedia = $exercise->media->whereIn('type', ['video_url', 'video'])->first();
                    $colors = [
                        'Chest' => 'blue', 'Back' => 'violet', 'Shoulders' => 'cyan',
                        'Arms' => 'pink', 'Core' => 'orange', 'Legs' => 'green',
                        'Cardio' => 'red', 'Full Body' => 'zinc',
                    ];
                    $color = $colors[$exercise->muscle_group ?? ''] ?? 'zinc';
                @endphp

                <div class="group flex flex-col rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">

                    {{-- Inline video player --}}
                    @if ($videoMedia)
                        @php $thumb = $videoMedia->thumbnailUrl(); @endphp
                        <div x-data="{ open: false }" class="relative w-full aspect-video bg-zinc-900 shrink-0 overflow-hidden">

                            {{-- Thumbnail + play button (shown when closed) --}}
                            <template x-if="!open">
                                <button @click="open = true"
                                    class="absolute inset-0 w-full h-full group/play">
                                    @if ($thumb)
                                        <img src="{{ $thumb }}" alt="video thumbnail"
                                            class="w-full h-full object-cover transition duration-300 group-hover/play:scale-105" />
                                        <div class="absolute inset-0 bg-black/10 group-hover/play:bg-black/30 transition"></div>
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-zinc-800">
                                            <flux:icon name="film" class="size-10 text-zinc-600" />
                                        </div>
                                    @endif
                                    {{-- Play icon --}}
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <div class="size-14 rounded-full bg-black/50 backdrop-blur-sm flex items-center justify-center
                                                    group-hover/play:scale-110 group-hover/play:bg-black/70 transition duration-200">
                                            <svg class="size-7 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    @if ($videoMedia->isYoutube())
                                        <div class="absolute bottom-2 left-2 rounded px-1.5 py-0.5 bg-black/70 text-white text-xs font-medium">
                                            {{ __('YouTube') }}
                                        </div>
                                    @endif
                                </button>
                            </template>

                            {{-- Actual player (shown when open — iframe/video only loads here) --}}
                            <template x-if="open">
                                <div class="absolute inset-0">
                                    @if ($videoMedia->isYoutube())
                                        <iframe src="{{ $videoMedia->playUrl() }}"
                                            class="w-full h-full"
                                            frameborder="0"
                                            allow="clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen></iframe>
                                    @else
                                        <video src="{{ $videoMedia->playUrl() }}" controls autoplay
                                            class="w-full h-full object-contain bg-black"></video>
                                    @endif
                                    {{-- Close button --}}
                                    <button @click="open = false"
                                        class="absolute top-2 right-2 size-7 rounded-full bg-black/60 text-white flex items-center justify-center text-lg hover:bg-black/80 transition"
                                        title="{{ __('Close') }}">×</button>
                                </div>
                            </template>

                        </div>
                    @elseif ($images->isNotEmpty())
                        <div class="relative w-full aspect-video overflow-hidden shrink-0">
                            <img src="{{ $images->first()->publicUrl() }}" alt=""
                                class="w-full h-full object-cover" />
                        </div>
                    @endif

                    {{-- Card body --}}
                    <div class="flex flex-col gap-3 p-4 flex-1">

                        {{-- Top row: name + actions --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold truncate">{{ $exercise->name }}</div>
                                @if ($exercise->muscle_group)
                                    <flux:badge size="sm" color="{{ $color }}" class="mt-1">{{ __($exercise->muscle_group) }}</flux:badge>
                                @endif
                            </div>
                            <div class="flex gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition">
                                <flux:button size="sm" variant="ghost" icon="pencil"
                                    wire:click="edit({{ $exercise->id }})" />
                                <flux:button size="sm" variant="ghost" icon="trash"
                                    wire:click="delete({{ $exercise->id }})"
                                    :wire:confirm="__('Delete \':name\'? It will be removed from all plans.', ['name' => $exercise->name])" />
                            </div>
                        </div>

                        {{-- Additional image thumbnails (when video is shown as cover) --}}
                        @if ($videoMedia && $images->isNotEmpty())
                            <div class="flex gap-1.5 flex-wrap">
                                @foreach ($images->take(3) as $img)
                                    <img src="{{ $img->publicUrl() }}" alt=""
                                        class="h-12 w-12 rounded-lg object-cover border border-zinc-200 dark:border-zinc-700" />
                                @endforeach
                                @if ($images->count() > 3)
                                    <div class="h-12 w-12 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-xs text-zinc-500 font-medium">
                                        +{{ $images->count() - 3 }}
                                    </div>
                                @endif
                            </div>
                        @elseif (!$videoMedia && $images->count() > 1)
                            {{-- Show remaining images as thumbnails --}}
                            <div class="flex gap-1.5 flex-wrap">
                                @foreach ($images->skip(1)->take(3) as $img)
                                    <img src="{{ $img->publicUrl() }}" alt=""
                                        class="h-12 w-12 rounded-lg object-cover border border-zinc-200 dark:border-zinc-700" />
                                @endforeach
                                @if ($images->count() > 4)
                                    <div class="h-12 w-12 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-xs text-zinc-500 font-medium">
                                        +{{ $images->count() - 4 }}
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Description --}}
                        @if ($exercise->description)
                            <p class="text-sm text-zinc-500 line-clamp-2 leading-relaxed">{{ $exercise->description }}</p>
                        @endif

                        {{-- Footer --}}
                        <div class="mt-auto pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <flux:text class="text-xs text-zinc-400">
                                {{ $exercise->workout_plan_exercises_count === 1
                                    ? __('Used in :count plan', ['count' => $exercise->workout_plan_exercises_count])
                                    : __('Used in :count plans', ['count' => $exercise->workout_plan_exercises_count]) }}
                            </flux:text>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
