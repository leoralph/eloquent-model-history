<?php

namespace LeoRalph\History\Tests;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use LeoRalph\History\HasOperations;

class User extends Authenticatable
{
    use Notifiable, HasOperations;
    public $timestamps = false;
    protected $guarded = [];
    protected $hidden = [];
}