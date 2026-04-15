<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $fillable = ['workout_plan_id', 'client_id', 'trainer_id', 'scheduled_date', 'notes', 'series_id'];

    protected function casts(): array
    {
        return ['scheduled_date' => 'date'];
    }

    public function workoutPlan()
    {
        return $this->belongsTo(WorkoutPlan::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function exerciseLogs()
    {
        return $this->hasMany(ExerciseLog::class);
    }

    public function isCompleted(): bool
    {
        $total = $this->workoutPlan->workoutPlanExercises()->count();
        if ($total === 0) {
            return false;
        }
        $done = $this->exerciseLogs()->whereNotNull('completed_at')->count();

        return $done >= $total;
    }

    public function completionPercent(): int
    {
        $total = $this->workoutPlan->workoutPlanExercises()->count();
        if ($total === 0) {
            return 0;
        }
        $done = $this->exerciseLogs()->whereNotNull('completed_at')->count();

        return (int) round($done / $total * 100);
    }
}
