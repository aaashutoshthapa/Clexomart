@extends('layouts.app')

@section('title', 'Sign Up')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/usersignup.css') }}">
@endpush

@section('content')
    <div class="signup-box">
        <h1 class="title">Welcome</h1>

        @if (session('success'))
            <div class="notification is-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="notification is-danger">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form id="signup-form" method="POST" action="{{ route('signup.submit') }}">
            @csrf
            <div class="field">
                <label class="label">First Name</label>
                <div class="control">
                    <input id="first-name" class="input" type="text" name="first_name" placeholder="First Name" value="{{ old('first_name') }}" required>
                </div>
                <p id="first-name-error" class="error-message">Please enter your first name.</p>
                @error('first_name')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label class="label">Last Name</label>
                <div class="control">
                    <input id="last-name" class="input" type="text" name="last_name" placeholder="Last Name" value="{{ old('last_name') }}" required>
                </div>
                <p id="last-name-error" class="error-message">Please enter your last name.</p>
                @error('last_name')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label class="label">Email</label>
                <div class="control">
                    <input id="email" class="input" type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
                </div>
                <p id="email-error" class="error-message">Please enter a valid email address.</p>
                @error('email')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label class="label">Contact Number</label>
                <div class="control">
                    <input id="contact-no" class="input" type="tel" name="contact_no" placeholder="Contact Number" value="{{ old('contact_no') }}" required>
                </div>
                <p id="contact-no-error" class="error-message">Please enter a valid contact number.</p>
                @error('contact_no')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label class="label">Password</label>
                <div class="control">
                    <input id="password" class="input" type="password" name="password" placeholder="Password" required>
                </div>
                <p id="password-error" class="error-message">Password must be at least 8 characters long.</p>
                @error('password')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label class="label">Confirm Password</label>
                <div class="control">
                    <input id="confirm-password" class="input" type="password" name="password_confirmation" placeholder="Confirm Password" required>
                </div>
                <p id="confirm-password-error" class="error-message">Passwords do not match.</p>
            </div>

            <div class="field">
                <label class="label">Select a Role</label>
                <div class="control">
                    <div class="select">
                        <select id="role" name="role" required>
                            <option value="">Select a role</option>
                            <option value="customer" {{ old('role') == 'customer' ? 'selected' : '' }}>Customer</option>
                            <option value="trader" {{ old('role') == 'trader' ? 'selected' : '' }}>Trader</option>
                        </select>
                    </div>
                </div>
                <p id="role-error" class="error-message">Please select a role.</p>
                @error('role')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>

            <button id="signup-btn" type="submit" class="button is-primary">Sign up</button>
            <a href="{{ url('/signin.html') }}" class="signup-link">Already have an account? Sign in</a>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/usersignup.js') }}"></script>
@endpush