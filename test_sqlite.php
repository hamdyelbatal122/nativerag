<?php

$pdo = new PDO('sqlite::memory:');
if (method_exists($pdo, 'sqliteCreateFunction')) {
    echo 'sqliteCreateFunction exists!';
} else {
    echo 'sqliteCreateFunction DOES NOT exist!';
}
