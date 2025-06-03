<?php

namespace App\Http\Controllers;

use App\Models\Product;     // ← add this
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $products = Product::limit(12)->get();

        return view('index', compact('products'));
    }
}
