<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'inbox_id',
        'slug',
        'subject',
        'body_html',
        'locale',
        'enabled',
    ];
}
