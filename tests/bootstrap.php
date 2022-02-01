<?php
/**
* PHPUnit bootstrap file
*/

if(!is_file(__DIR__ . '/../vendor/autoload.php')){
	exit('Please install "vendor" directory - run "composer install" in the root directory.');
}

require_once(__DIR__ . '/../vendor/autoload.php');
