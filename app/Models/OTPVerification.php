<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OTPVerification extends Model
{
    use HasFactory;
    protected $table = 'otpverification'; 
    protected $fillable = ['user_id', 'otp', 'expire_at'];
}
