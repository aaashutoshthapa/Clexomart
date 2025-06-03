<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class User1Controller extends Controller
{
    public function showForm()
    {
        return view('signup_form'); // loads signup.blade.php
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'user_type'  => 'required|string|max:50',
            'email'      => 'required|email|max:255',
            'contact_no' => 'required|numeric|digits_between:7,15',
            'password'   => 'required|string|min:6|max:255',
        ]);

        DB::insert(
            "INSERT INTO USER1 (
                first_name, last_name, user_type, email, contact_no, password
            ) VALUES (
                :first_name, :last_name, :user_type, :email, :contact_no, :password
            )",
            $validated
        );

        return redirect('/signup_form')->with('success', 'Account created successfully!');
    }

}

