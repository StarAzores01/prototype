<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetItem extends Model
{
    protected $fillable = [
        'training_id', 'category', 'description',
        'quantity', 'unit_cost', 'logged_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity'  => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total'     => 'decimal:2',
        ];
    }

    // Category options shared between the model and views.
    public const CATEGORIES = [
        'Food',
        'Materials',
        'Transportation',
        'Venue',
        'Honorarium',
        'Printing',
        'Other',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function loggedBy()
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
