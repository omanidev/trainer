<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExerciseLog extends Model
{
    protected $fillable = [
        'assignment_id',
        'workout_plan_exercise_id',
        'client_id',
        'completed_at',
        'notes',
        'weight',
        'weight_unit',
        'sets_data',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'weight' => 'decimal:2',
            'sets_data' => 'array',
        ];
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

    public function exercise()
    {
        return $this->hasOneThrough(
            Exercise::class,
            WorkoutPlanExercise::class,
            'id',
            'id',
            'workout_plan_exercise_id',
            'exercise_id'
        );
    }
}
