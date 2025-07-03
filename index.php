<?php

use App\Models\User;
use App\Models\UserType;

require __DIR__ . '/vendor/autoload.php';


$user = User::find(1);

dd($user);