<?php

spl_autoload_register(function ($class) {
    if (strpos($class, "Test") > 0) {
        include 'tests/' . $class . '.php';
    } else {
        include 'classes/' . $class . '.php';
    }
});
