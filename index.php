<?php

use App\Models\User;
use App\Models\UserType;

require __DIR__ . '/vendor/autoload.php';

$user = User::create([
    'name' => 'Igor',
    'email' => 'igor@gmail.com',
    'user_type' => 1,
]);

//$users = User::where('idd', 123)->get();

//dd($users);