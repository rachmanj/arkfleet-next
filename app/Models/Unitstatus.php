<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unitstatus extends Model
{
    protected $fillable = ['name', 'color', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
