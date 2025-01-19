<?php

namespace app\controller;

class User
{
    public function index()
    {
        echo "User Index Page";
    }

    public function a()
    {
        echo segment(1);
    }
}
