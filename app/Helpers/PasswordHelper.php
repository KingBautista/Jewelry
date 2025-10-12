<?php

namespace App\Helpers;
use Hash;

class PasswordHelper 
{
    // permited string for salting
    private static $permitedString = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /* 	gen_salt
	*	params
	*	return random string
    */
    public static function generateSalt() {
    	return substr(str_shuffle(static::$permitedString), 0, 20);
    }

    /* gen_password
    *  params $salt, $password
	*  return $password
    */
    public static function generatePassword($salt, $password) {		
		return Hash::make($salt.$password.env('PEPPER_HASH'));
    }

    /* verify_password
    *  params $salt, $password, $hash
	*  return boolean
    */
    public static function verifyPassword($salt, $password, $hash) {		
		return Hash::check($salt.$password.env('PEPPER_HASH'), $hash);
    }

}