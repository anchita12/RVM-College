<?php

const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = 'mysql';
const DB_NAME = 'rvm_jaunpur3';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_error) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');

