<?php

namespace App\Http\Controllers;

class StocktakeController extends Controller
{
    public function index()
    {
        return view('stocktake.index');
    }
}
