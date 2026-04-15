<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExerciseLog extends Model
{
    protected $fillable = ['assignment_id', 'workout_plan_exercise_id', 'client_id', 'completed_at', 'notes'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function workoutPlanExercise()
    {
        return $this->belongsTo(WorkoutPlanExercise::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
