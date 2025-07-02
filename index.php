<?php

use App\Models\User;
use App\Models\UserType;

require __DIR__ . '/vendor/autoload.php';

//$user = User::create([
//    'name' => 'Igor',
//    'email' => 'igor@gmail.com',
//    'user_type' => 1,
//]);
//
//dd($user, 'finish');


$users = User::get();

dd($users);