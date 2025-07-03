<?php

use App\Models\User;
use App\Models\UserType;

require __DIR__ . '/vendor/autoload.php';

//User::create([
//    'name' => 'Sasha','email' => 'asdasdsa@asdasdas.asd'
//]);
//
//User::create([
//    'name' => 'Voron', 'email' => 'test@asdasdas.asd'
//]);
//
//
//User::create([
//    'name' => 'Roman', 'email' => 'roman@asdasdas.asd'
//]);
dd(User::get(['email', 'user_id']));


$users = User::where('user_id', '>', 2)->where('name', 'Voron')->get();

dd($users);