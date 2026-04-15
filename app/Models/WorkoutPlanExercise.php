<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutPlanExercise extends Model
{
    protected $fillable = [
        'workout_plan_id', 'exercise_id', 'sets', 'reps',
        'duration_seconds', 'sort_order', 'notes',
    ];

    public function workoutPlan()
    {
        return $this->belongsTo(WorkoutPlan::class);
    }

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }

    public function logs()
    {
        return $this->hasMany(ExerciseLog::class);
    }
}
