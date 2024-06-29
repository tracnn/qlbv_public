<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QueueNumber extends Model
{
    protected $fillable = array('department_code', 'phone_number', 'number', 'is_sms_sended');
}
