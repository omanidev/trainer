<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExerciseMedia extends Model
{
    protected $fillable = ['exercise_id', 'type', 'path', 'url', 'sort_order'];

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isUploadedVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isExternalVideo(): bool
    {
        return $this->type === 'video_url';
    }

    public function isYoutube(): bool
    {
        return $this->isExternalVideo()
            && (bool) preg_match('/(?:youtube\.com|youtu\.be)/', $this->url ?? '');
    }

    /**
     * Extract the YouTube video ID from any YouTube URL variant:
     * - youtube.com/watch?v=ID
     * - m.youtube.com/watch?v=ID
     * - youtu.be/ID
     * - youtube.com/shorts/ID
     */
    public function youtubeId(): ?string
    {
        if (!$this->isYoutube() || !$this->url) {
            return null;
        }

        $parsed = parse_url($this->url);
        $host   = $parsed['host'] ?? '';
        $path   = $parsed['path'] ?? '';

        // youtu.be/VIDEO_ID  or  youtube.com/shorts/VIDEO_ID
        if (str_contains($host, 'youtu.be') || str_contains($path, '/shorts/')) {
            $id = trim(basename($path), '/');
            return $id ?: null;
        }

        // youtube.com/watch?v=VIDEO_ID
        parse_str($parsed['query'] ?? '', $params);
        return $params['v'] ?? null;
    }

    /**
     * Returns the embed/playback URL.
     * For YouTube: returns the embed URL (iframe).
     * For uploaded video: returns the public storage URL.
     * For other external URLs: returns the URL as-is.
     */
    public function playUrl(): ?string
    {
        if ($this->isYoutube()) {
            $id = $this->youtubeId();
            return $id
                ? "https://www.youtube.com/embed/{$id}?rel=0&modestbranding=1"
                : $this->url;
        }

        if ($this->isExternalVideo()) {
            return $this->url;
        }

        if ($this->isUploadedVideo() && $this->path) {
            return asset('storage/' . $this->path);
        }

        return null;
    }

    /**
     * Returns a thumbnail image URL (YouTube only for now).
     */
    public function thumbnailUrl(): ?string
    {
        $id = $this->youtubeId();
        return $id ? "https://img.youtube.com/vi/{$id}/hqdefault.jpg" : null;
    }

    /**
     * Public URL for images and uploaded videos.
     */
    public function publicUrl(): ?string
    {
        if (($this->isImage() || $this->isUploadedVideo()) && $this->path) {
            return asset('storage/' . $this->path);
        }
        return $this->url;
    }
}
