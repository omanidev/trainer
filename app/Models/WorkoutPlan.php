<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutPlan extends Model
{
    protected $fillable = ['trainer_id', 'name', 'notes'];

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function workoutPlanExercises()
    {
        return $this->hasMany(WorkoutPlanExercise::class)->orderBy('sort_order');
    }

    public function exercises()
    {
        return $this->belongsToMany(Exercise::class, 'workout_plan_exercises')
            ->withPivot(['sets', 'reps', 'duration_seconds', 'sort_order', 'notes'])
            ->orderByPivot('sort_order');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
}
