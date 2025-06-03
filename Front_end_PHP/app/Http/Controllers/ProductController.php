<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show($id)
    {
        // Fetch the product by ID
        $product = Product::with('category', 'shop')->findOrFail($id);

        // Fetch reviews for the product
        $reviews = Review::where('product_id', $id)->get();

        // Fetch recommended products (e.g., same category, excluding current product)
        $recommendedProducts = Product::where('category_id', $product->category_id)
            ->where('product_id', '!=', $id)
            ->take(4)
            ->get();

        // Return the product detail view with data
        return view('productdetail', compact('product', 'reviews', 'recommendedProducts'));
    }
}