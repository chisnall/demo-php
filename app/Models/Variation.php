<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['client_id', 'type', 'subtype', 'payload', 'status'])]

class Variation extends Model
{
    protected $casts = [
        'payload' => 'array',
    ];
}
