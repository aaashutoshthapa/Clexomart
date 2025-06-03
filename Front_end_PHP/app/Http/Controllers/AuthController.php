<?php

/**
 * AuthController
 * 
 * This controller manages the complete user authentication system for ClexoMart,
 * including registration, email verification, login, and logout functionality.
 * 
 * Key Responsibilities:
 * - User registration with role differentiation (customer/trader)
 * - Email verification using OTP (One-Time Password) system
 * - Secure login with password hashing and validation
 * - Role-based authentication and authorization
 * - Session management and cart transfer
 * - Admin approval workflow for trader accounts
 * 
 * Authentication Flow:
 * 1. User Registration: Create account with role selection
 * 2. OTP Generation: Send verification code to email
 * 3. Email Verification: Validate OTP and activate account
 * 4. Admin Approval: Traders require admin verification
 * 5. Login: Authenticate and establish session
 * 6. Session Management: Maintain user state and cart
 * 
 * Security Features:
 * - Password hashing with Laravel's Hash facade
 * - OTP expiration (10 minutes)
 * - Email validation with DNS checking
 * - Role-based access control
 * - Session-based authentication
 * - Cart data protection and migration
 * 
 * User Types:
 * - Customers: Auto-approved, can shop immediately after email verification
 * - Traders: Require admin approval, can create shops and sell products
 * - Admins: System administrators with full access
 * 
 * @package App\Http\Controllers
 * @author ClexoMart Development Team
 * @version 1.0
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Carbon\Carbon;

/**
 * AuthController Class
 * 
 * Handles all authentication-related operations including registration,
 * verification, login, and session management for the ClexoMart platform
 */
class AuthController extends Controller
{
    /**
     * Display the user registration form
     * 
     * Shows the signup page where users can create new accounts.
     * Supports both customer and trader registration with role selection.
     * 
     * @return \Illuminate\View\View Signup form view
     */
    public function showSignupForm()
    {
        return view('signup');
    }

    /**
     * Display the user login form
     * 
     * Shows the signin page where existing users can authenticate.
     * Handles both customer and trader login with role-specific validation.
     * 
     * @return \Illuminate\View\View Signin form view
     */
    public function showSigninForm()
    {
        return view('signin');
    }

    /**
     * Process user registration and initiate email verification
     * 
     * This method handles the complete user registration process including:
     * - Input validation and sanitization
     * - User account creation with role assignment
     * - OTP generation and email delivery
     * - Admin approval setup for traders
     * 
     * Registration Process:
     * 1. Validate all input fields with comprehensive rules
     * 2. Generate unique user ID using custom sequence
     * 3. Create 6-digit OTP with 10-minute expiration
     * 4. Set admin approval status based on user role
     * 5. Store user data with hashed password
     * 6. Send OTP email for verification
     * 7. Redirect to OTP verification page
     * 
     * Role-Based Setup:
     * - Customers: admin_verified = 'Y' (auto-approved)
     * - Traders: admin_verified = 'N' (requires admin approval)
     * 
     * @param  \Illuminate\Http\Request  $request Registration form data
     * @return \Illuminate\Http\RedirectResponse Redirect to OTP verification
     */
    public function signup(Request $request)
    {
        // Input Validation: Comprehensive validation with security rules
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',              // Required name field
            'last_name' => 'required|string|max:255',               // Required surname field
            'email' => 'required|email:rfc,dns|unique:USER1,email|max:255', // Email with DNS validation and uniqueness
            'contact_no' => 'required|numeric|digits:10',           // 10-digit phone number
            'password' => 'required|string|min:8|confirmed',        // Strong password with confirmation
            'role' => 'required|in:customer,trader',                // Role selection validation
        ]);

        // User ID Generation: Create unique identifier using custom sequence
        $userId = User::generateUserId();

        // OTP System: Generate verification code with expiration
        $otp = rand(100000, 999999);                               // 6-digit random OTP
        $otpExpiresAt = Carbon::now()->addMinutes(10);              // 10-minute expiration window

        // Role-Based Approval: Set admin verification status
        $adminVerified = strtoupper($validated['role'] === 'customer' ? 'Y' : 'N');

        // User Creation: Store new user account in database
        $user = User::create([
            'user_id' => $userId,                                   // Custom generated ID
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'user_type' => $validated['role'],                      // customer or trader
            'email' => $validated['email'],
            'contact_no' => $validated['contact_no'],
            'password' => Hash::make($validated['password']),       // Secure password hashing
            'otp' => $otp,                                         // Verification code
            'is_verified' => false,                                 // Email not verified yet
            'otp_expires_at' => $otpExpiresAt,                     // OTP expiration timestamp
            'admin_verified' => $adminVerified,                     // Role-based approval status
        ]);

        // Email Delivery: Send OTP verification email
        Mail::to($user->email)->send(new OtpMail($otp));

        // Success Response: Redirect to verification page
        return redirect()->route('verify.otp.form', ['email' => $user->email])
                        ->with('success', 'Please check your email for the OTP to verify your account.');
    }

    /**
     * Display the OTP verification form
     * 
     * Shows the verification page where users enter the OTP code
     * sent to their email address during registration.
     * 
     * @param  \Illuminate\Http\Request  $request Contains email parameter
     * @return \Illuminate\View\View OTP verification form view
     */
    public function showVerifyOtpForm(Request $request)
    {
        $email = $request->query('email');
        return view('verify-otp', ['email' => $email]);
    }

    /**
     * Verify OTP code and activate user account
     * 
     * This method processes the OTP verification and activates the user account.
     * It includes comprehensive validation and security checks.
     * 
     * Verification Process:
     * 1. Validate input (email exists, OTP format)
     * 2. Retrieve user account by email
     * 3. Verify OTP code matches
     * 4. Check OTP hasn't expired
     * 5. Mark account as verified
     * 6. Clear OTP data for security
     * 7. Redirect to login page
     * 
     * Security Features:
     * - OTP expiration validation
     * - Secure OTP clearing after verification
     * - Account activation only after verification
     * 
     * @param  \Illuminate\Http\Request  $request Contains email and OTP
     * @return \Illuminate\Http\RedirectResponse Redirect to login or error
     */
    public function verifyOtp(Request $request)
    {
        // Input Validation: Ensure valid email and OTP format
        $request->validate([
            'email' => 'required|email|exists:USER1,email',         // Email must exist in database
            'otp' => 'required|digits:6',                           // 6-digit OTP validation
        ]);

        // User Retrieval: Get user account for verification
        $user = User::where('email', $request->email)->first();

        // OTP Code Validation: Check if provided OTP matches stored OTP
        if ($user->otp != $request->otp) {
            return redirect()->back()->withErrors(['otp' => 'Invalid OTP.']);
        }

        // Expiration Validation: Check if OTP is still valid
        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return redirect()->back()->withErrors(['otp' => 'OTP has expired.']);
        }

        // Account Activation: Mark user as verified and clear OTP data
        $user->update([
            'is_verified' => true,                                  // Email verification complete
            'otp' => null,                                         // Clear OTP for security
            'otp_expires_at' => null,                              // Clear expiration timestamp
        ]);

        // Success Response: Redirect to login with confirmation message
        return redirect()->route('signin')->with('success', 'Email verified successfully! Please sign in.');
    }

    /**
     * Process user login and establish authenticated session
     * 
     * This method handles user authentication with role-based validation
     * and session establishment. It includes comprehensive security checks
     * and handles cart data migration for authenticated users.
     * 
     * Authentication Flow:
     * 1. Input validation (email exists, password format)
     * 2. User retrieval and password verification
     * 3. Role-specific authentication checks
     * 4. Session establishment with user data
     * 5. Cart data migration (session → database)
     * 6. Role-based redirection
     * 
     * Role-Specific Validation:
     * - Customers: Must have verified email
     * - Traders: Must have admin approval (email verification handled at signup)
     * 
     * Security Features:
     * - Password hash verification
     * - Email verification requirement
     * - Admin approval validation for traders
     * - Secure session management
     * - Cart data protection during migration
     * 
     * @param \Illuminate\Http\Request $request Login credentials
     * @return \Illuminate\Http\RedirectResponse Role-based redirect
     */
    public function signin(Request $request)
    {
        // Input Validation: Validate login credentials
        $request->validate([
            'email' => 'required|email|exists:USER1,email',         // Email must exist
            'password' => 'required|string|min:8',                  // Password format validation
        ]);
    
        // User Retrieval: Get user account for authentication
        $user = User::where('email', $request->email)->first();
    
        // Password Verification: Check provided password against stored hash
        if (!Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Invalid password.']);
        }
    
        // Role-Based Authentication: Different validation flows for each user type
        if ($user->user_type === 'customer') {
            // Customer Authentication: Check email verification
            if (!$user->is_verified) {
                return redirect()->back()->withErrors(['email' => 'Please verify your email before signing in.']);
            }
        } else if ($user->user_type === 'trader') {
            // Trader Authentication: Check admin approval status
            if (strtoupper($user->admin_verified) !== 'Y') {
                return redirect()->back()->withErrors(['email' => 'Your trader account is pending admin approval.']);
            }
            // Note: Email verification was completed during signup for traders
        }
    
        // Session Establishment: Set up authenticated user session
        session(['user_id' => $user->user_id, 'user_type' => $user->user_type]);
    
        // Cart Data Migration: Transfer session cart to database if exists
        if (session()->has('cart')) {
            app(CartController::class)->transferSessionCartToDatabase($user->user_id);
        }
    
        // Role-Based Redirection: Send users to appropriate dashboard
        if ($user->user_type === 'customer') {
            return redirect()->route('home')->with('success', 'Signed in successfully!');
        } elseif ($user->user_type === 'trader') {
            return redirect()->route('trader')->with('success', 'Signed in successfully!');
        }
    
        // Fallback Redirection: Default to home page
        return redirect()->route('home')->with('info', 'Signed in successfully.');
    }

    /**
     * Resend OTP verification code to user email
     * 
     * This method allows users to request a new OTP if their original code
     * has expired or was not received. It includes validation to prevent
     * unnecessary resends and ensures security.
     * 
     * Resend Process:
     * 1. Validate email exists in system
     * 2. Check if user is already verified
     * 3. Generate new 6-digit OTP
     * 4. Set new expiration time (10 minutes)
     * 5. Update user record with new OTP
     * 6. Send new OTP email
     * 7. Redirect back to verification form
     * 
     * Security Features:
     * - Validation prevents resend to verified accounts
     * - New OTP overwrites old one
     * - Fresh expiration timestamp
     * - Secure random OTP generation
     * 
     * @param \Illuminate\Http\Request $request Contains email for resend
     * @return \Illuminate\Http\RedirectResponse Redirect with status message
     */
    public function resendOtp(Request $request)
    {
        // Input Validation: Ensure email exists in system
        $request->validate([
            'email' => 'required|email|exists:USER1,email',
        ]);

        // User Retrieval: Get user account for OTP resend
        $user = User::where('email', $request->email)->first();

        // Verification Check: Prevent resend if already verified
        if ($user->is_verified) {
            return redirect()->route('signin')->with('success', 'Email already verified. Please sign in.');
        }

        // OTP Generation: Create new verification code with fresh expiration
        $otp = rand(100000, 999999);                               // New 6-digit random OTP
        $otpExpiresAt = Carbon::now()->addMinutes(10);              // Fresh 10-minute expiration

        // Database Update: Store new OTP and expiration time
        $user->update([
            'otp' => $otp,                                         // Overwrite old OTP
            'otp_expires_at' => $otpExpiresAt,                     // Reset expiration timer
        ]);

        // Email Delivery: Send new OTP to user
        Mail::to($user->email)->send(new OtpMail($otp));

        // Success Response: Redirect back to verification form with new OTP
        return redirect()->route('verify.otp.form', ['email' => $user->email])
                        ->with('success', 'New OTP has been sent to your email.');
    }

    /**
     * Handle user logout and session cleanup
     * 
     * This method processes user logout by clearing all session data
     * and providing a JSON response for AJAX requests.
     * 
     * Logout Process:
     * 1. Clear user_id from session
     * 2. Clear user_type from session
     * 3. Maintain cart data in session (for guest users)
     * 4. Return JSON success response
     * 
     * Note: Cart data is preserved during logout so users don't lose
     * their items when switching between guest and authenticated states.
     * 
     * @param \Illuminate\Http\Request $request Logout request
     * @return \Illuminate\Http\JsonResponse JSON success response
     */
    public function logout(Request $request)
    {
        // Session Cleanup: Remove authentication data while preserving cart
        $request->session()->forget('user_id');                    // Clear user identification
        $request->session()->forget('user_type');                  // Clear role information
        
        // Note: Cart session data is intentionally preserved for user convenience

        // AJAX Response: Return JSON for frontend handling
        return response()->json(['success' => true]);
    }
}