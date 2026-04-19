<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodyWeightLog extends Model
{
    protected $fillable = ['client_id', 'weight', 'unit', 'notes'];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
        ];
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
