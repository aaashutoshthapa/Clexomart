<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log ;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\PDO;


class ProfileController extends Controller
{
    public function profile(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            // User not logged in, redirect to signin
            return redirect()->route('signin')->withErrors('Please sign in first.');
        }
        // Fetch user from database
        $user = User::where('user_id', $userId)->first();
        if (!$user) {
            // User not found (session invalid)
            return redirect()->route('signin')->withErrors('User not found. Please sign in again.');
        }
        // Return profile view with user data
        return view('profile', ['user' => $user]);
    }

    public function editProfile(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return redirect()->route('signin')->withErrors('Please sign in first.');
        }
        $user = User::where('user_id', $userId)->firstOrFail();
        return view('profile-edit', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        //  Get the current user ID from the session
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return redirect()->route('signin')->withErrors('Please sign in first.');
        }

        //  Fetch the user from the database
        $user = User::where('user_id', $userId)->firstOrFail();

        //  Validate incoming data
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'contact_no' => 'required|numeric|digits_between:7,15',
            'email' => 'required|email|max:255|unique:USER1,email,' . $user->user_id . ',user_id',
            'profile_image' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
        ]);

        //  Update basic fields 
        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'contact_no' => $validated['contact_no'],
            'email' => $validated['email'],
        ]);

        
        //handle image
       if ($request->hasFile('profile_image')) {
        $file = $request->file('profile_image');
        $imageData = file_get_contents($file->getRealPath());

        DB::beginTransaction();

        // Update non-BLOB columns first
        DB::update('UPDATE USER1 SET 
            "USER_IMAGE_MIMETYPE" = ?, 
            "USER_IMAGE_FILENAME" = ?, 
            "USER_IMAGE_LASTUPD" = SYSTIMESTAMP 
            WHERE "USER_ID" = ?', [
            $file->getClientMimeType(),
            $file->getClientOriginalName(),
            $userId,
        ]);

        // Set BLOB column to EMPTY_BLOB()
        DB::update('UPDATE USER1 SET "USER_IMAGE" = EMPTY_BLOB() WHERE "USER_ID" = ?', [$userId]);

        // Update BLOB directly with PDO
        $pdo = DB::connection()->getPdo();
        $stmt = $pdo->prepare('UPDATE USER1 SET "USER_IMAGE" = :image WHERE "USER_ID" = :userId');
        $stmt->bindParam(':image', $imageData, \PDO::PARAM_LOB);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();

        DB::commit();
    }
        // Redirect back to the profile page with a success message
        return redirect()->route('profile')->with('success', 'Profile updated successfully.');
    }


    public function showChangePasswordForm(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return redirect()->route('signin')->withErrors('Please sign in first.');
        }
        return view('profile-pasw');
    }

    public function changePassword(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return redirect()->route('signin')->withErrors('Please sign in first.');
        }

        $user = User::where('user_id', $userId)->firstOrFail();

        $validated = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // Check if current password matches
        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        // Update password with hashing
        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return redirect()->route('profile')->with('success', 'Password changed successfully.');
    }

    public function showProfileImage($id)
{
    $imageData = DB::selectOne('SELECT "USER_IMAGE" AS user_image, "USER_IMAGE_MIMETYPE" AS user_image_mimetype 
                                FROM USER1 
                                WHERE USER_ID = :id', ['id' => $id]);

    if ($imageData && $imageData->user_image) {
        return response($imageData->user_image)
            ->header('Content-Type', $imageData->user_image_mimetype);
    }

    // If no image found, return default image
    return response()->file(public_path('images/default.png'));
}



}
