<?php

use App\Models\User;

require __DIR__ . '/vendor/autoload.php';

// let's drop old tests if there are without any exception:
@unlink('data/user.json');

// creates one record with user_id = 1
User::create([
    'name' => 'Sasha', 'email' => 'sasha@gmail.com'
]);

// creates one record with user_id = 2
User::create([
    'name' => 'Yaniv', 'email' => 'yaniv@gmail.com'
]);

dump('you may see that count is 2', User::count());

dump('you\'ll see 2 records in Collection class:', User::get());

// creates one record with user_id = 3
User::create([
    'name' => 'Alex', 'email' => 'alex@gmail.com'
]);

// try to use chain: user_id not 1 and name is Sasha, so, there is nothing to show:
$users = User::where('user_id', '>', 1)->where('name', 'Sasha')->get();
dump('chain: user_id not 1 and name is Sasha, so - nothing', $users);

// and now it founds record:
$users = User::where('user_id', '>', 0)->where('name', 'Sasha')->get();

dump('show only one attribute: email', User::first()->email);

// founds the same record (Sasha) and removes it, so count is 0:
User::find(1)->destroy();
dump('nothing found, count is 0:', User::where('user_id', 1)->count());

// update email:
User::find(2)->update([
    'name' => 'Somebody'
]);
dump('updated email is `Somebody` for user with email yaniv@gmail.com', User::find(2));


// usage of select fields:
dump('shows only email and name per each model', User::get(['email', 'name']));

//collection first:
dump(User::get()->first());
