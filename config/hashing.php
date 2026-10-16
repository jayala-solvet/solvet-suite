<?php

return [
    'driver' => env('HASH_DRIVER', 'argon2id'),
    'bcrypt' => ['rounds' => env('BCRYPT_ROUNDS', 12), 'verify' => true],
    // password_verify accepts the existing bcrypt hashes; login upgrades them.
    'argon' => ['memory' => 65536, 'threads' => 1, 'time' => 4, 'verify' => false],
    'rehash_on_login' => true,
];
