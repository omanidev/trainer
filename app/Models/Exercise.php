<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    protected $fillable = ['trainer_id', 'name', 'description', 'muscle_group'];

    public function media()
    {
        return $this->hasMany(ExerciseMedia::class)->orderBy('sort_order');
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function workoutPlanExercises()
    {
        return $this->hasMany(WorkoutPlanExercise::class);
    }
}
