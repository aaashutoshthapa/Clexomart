<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        $products = Product::with('category')->paginate(8);
        return view('categorypage', compact('categories', 'products'));
    }

    public function show($category)
    {
        $category = Category::where('category_name', $category)->firstOrFail();
        $products = Product::where('category_id', $category->category_id)->with('category')->paginate(8);
        $categories = Category::all();
        return view('categorypage', compact('category', 'products', 'categories'));
    }
}