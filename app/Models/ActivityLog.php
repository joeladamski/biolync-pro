<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'actor_user_id', 'action', 'route', 'method', 'changes', 'meta'
    ];

    protected $casts = [
        'changes' => 'array',
        'meta' => 'array',
    ];
}
