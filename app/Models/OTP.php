<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OTP extends Model
{
    use HasFactory, Notifiable;
    
    protected $table = 'otps';

    protected $fillable = [
        'telegram_id',
        'otp',         
        'expires_at',  
    ];
}
