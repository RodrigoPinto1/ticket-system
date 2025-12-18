<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'function_id',
        'email',
        'phone',
        'mobile',
        'internal_notes',
        'is_active',
    ];

    public function function(): BelongsTo
    {
        return $this->belongsTo(ContactFunction::class, 'function_id');
    }

    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class, 'contact_entity')
            ->withTimestamps();
    }
}
