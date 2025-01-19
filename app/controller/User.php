<?php

namespace app\controller;

class User
{
    public function index()
    {
        echo "User Index Page";
    }

    public function a(int $id = null)
    {
        echo "User A Method";
        echo '<hr>';
        echo $id;
        echo '<hr>';
    }
}
