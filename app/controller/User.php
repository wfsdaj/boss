<?php

namespace app\controller;

class User
{
    public function index()
    {
        echo "User Index Page";
    }

    public function a(string $id = null)
    {
        echo "User A Method";
        echo '<hr>';
        echo (int)$id;
        echo '<hr>';
    }
}
