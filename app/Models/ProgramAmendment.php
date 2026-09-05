<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Immutable audit row for an EC unlocking a program and changing its budget or timeline. */
class ProgramAmendment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'program_id', 'field_changed', 'old_value', 'new_value', 'remark', 'amended_by',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function amendedBy()
    {
        return $this->belongsTo(User::class, 'amended_by');
    }
}
