<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Exercise;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Models\WorkoutPlanExercise;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create trainer
        $trainer = User::create([
            'name'     => 'Coach Alex',
            'email'    => 'trainer@example.com',
            'password' => Hash::make('password'),
            'role'     => 'trainer',
        ]);

        // Create clients
        $client1 = User::create([
            'name'       => 'Jordan Smith',
            'email'      => 'client@example.com',
            'password'   => Hash::make('password'),
            'role'       => 'client',
            'trainer_id' => $trainer->id,
        ]);

        $client2 = User::create([
            'name'       => 'Sam Rivera',
            'email'      => 'sam@example.com',
            'password'   => Hash::make('password'),
            'role'       => 'client',
            'trainer_id' => $trainer->id,
        ]);

        // Create exercises
        $exercises = collect([
            ['name' => 'Push-ups',       'muscle_group' => 'Chest',     'description' => 'Classic bodyweight push-up.'],
            ['name' => 'Squats',         'muscle_group' => 'Legs',      'description' => 'Bodyweight squat, feet shoulder-width apart.'],
            ['name' => 'Plank',          'muscle_group' => 'Core',      'description' => 'Hold a flat plank position.'],
            ['name' => 'Pull-ups',       'muscle_group' => 'Back',      'description' => 'Full range pull-up on a bar.'],
            ['name' => 'Lunges',         'muscle_group' => 'Legs',      'description' => 'Alternating forward lunges.'],
            ['name' => 'Dumbbell Curl',  'muscle_group' => 'Arms',      'description' => 'Bicep curl with dumbbells.'],
            ['name' => 'Shoulder Press', 'muscle_group' => 'Shoulders', 'description' => 'Overhead press with dumbbells.'],
            ['name' => 'Jumping Jacks',  'muscle_group' => 'Cardio',    'description' => 'Full-body warm-up exercise.'],
            ['name' => 'Deadlift',       'muscle_group' => 'Back',      'description' => 'Barbell deadlift with proper form.'],
            ['name' => 'Burpees',        'muscle_group' => 'Full Body', 'description' => 'High-intensity full-body movement.'],
        ])->map(fn($e) => Exercise::create(['trainer_id' => $trainer->id] + $e));

        // Create workout plans
        $upperBody = WorkoutPlan::create([
            'trainer_id' => $trainer->id,
            'name'       => 'Upper Body Strength',
            'notes'      => 'Focus on chest, back and shoulders.',
        ]);

        $lowerBody = WorkoutPlan::create([
            'trainer_id' => $trainer->id,
            'name'       => 'Leg Day',
            'notes'      => 'Quad and glute focused workout.',
        ]);

        $cardio = WorkoutPlan::create([
            'trainer_id' => $trainer->id,
            'name'       => 'Cardio Blast',
            'notes'      => 'High-intensity cardio circuit.',
        ]);

        // Add exercises to Upper Body
        $upperExercises = [$exercises[0], $exercises[3], $exercises[6], $exercises[5]];
        foreach ($upperExercises as $i => $ex) {
            WorkoutPlanExercise::create([
                'workout_plan_id' => $upperBody->id,
                'exercise_id'     => $ex->id,
                'sets'            => 3,
                'reps'            => 12,
                'sort_order'      => $i,
            ]);
        }

        // Add exercises to Leg Day
        $legExercises = [$exercises[1], $exercises[4], $exercises[2]];
        foreach ($legExercises as $i => $ex) {
            WorkoutPlanExercise::create([
                'workout_plan_id' => $lowerBody->id,
                'exercise_id'     => $ex->id,
                'sets'            => 4,
                'reps'            => 15,
                'sort_order'      => $i,
            ]);
        }

        // Add exercises to Cardio
        $cardioExercises = [$exercises[7], $exercises[9], $exercises[2]];
        foreach ($cardioExercises as $i => $ex) {
            WorkoutPlanExercise::create([
                'workout_plan_id' => $cardio->id,
                'exercise_id'     => $ex->id,
                'sets'            => 3,
                'reps'            => 20,
                'sort_order'      => $i,
            ]);
        }

        // Assign workouts to clients
        Assignment::create([
            'workout_plan_id' => $upperBody->id,
            'client_id'       => $client1->id,
            'trainer_id'      => $trainer->id,
            'scheduled_date'  => today(),
            'notes'           => 'Push hard today!',
        ]);

        Assignment::create([
            'workout_plan_id' => $lowerBody->id,
            'client_id'       => $client1->id,
            'trainer_id'      => $trainer->id,
            'scheduled_date'  => today()->addDays(2),
        ]);

        Assignment::create([
            'workout_plan_id' => $cardio->id,
            'client_id'       => $client2->id,
            'trainer_id'      => $trainer->id,
            'scheduled_date'  => today(),
        ]);

        Assignment::create([
            'workout_plan_id' => $upperBody->id,
            'client_id'       => $client2->id,
            'trainer_id'      => $trainer->id,
            'scheduled_date'  => today()->subDays(3),
        ]);
    }
}
