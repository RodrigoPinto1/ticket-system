<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactFunction extends Model
{
    use HasFactory;

    protected $table = 'contact_functions';

    protected $fillable = [
        'name',
        'description',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'function_id');
    }
}
