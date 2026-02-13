<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    // index
    public function index()
    {
        
        return view("settings.index");
    }
}
