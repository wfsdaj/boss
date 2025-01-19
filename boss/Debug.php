<?php

namespace boss;

use Exception;

class Debug extends Exception
{
    public function display()
    {
        if (DEBUG) {
            include __DIR__ . '/templates/debug.php';
        }
    }
}
