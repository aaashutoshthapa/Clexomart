@extends('layouts.app')

@section('title', 'Sign In')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/sign.css') }}">
@endpush

@section('content')
    <section class="section">
        <h1 class="title has-text-centered">Welcome Back</h1>
        <div class="container">
            <div class="column is-half is-offset-one-quarter box login-box">
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

                <form id="loginForm" method="POST" action="{{ route('signin.submit') }}">
                    @csrf
                    <div class="field">
                        <label class="label">Email</label>
                        <div class="control">
                            <input class="input" type="email" name="email" placeholder="Enter your email" value="{{ old('email') }}" required>
                        </div>
                        @error('email')
                            <p class="help is-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="label">Password</label>
                        <div class="control">
                            <input class="input" type="password" name="password" placeholder="Enter your password" required>
                        </div>
                        @error('password')
                            <p class="help is-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field mt-4">
                        <div class="control">
                            <button type="submit" class="button sign-btn is-fullwidth">Sign In</button>
                        </div>
                    </div>
                </form>

                <p class="has-text-centered mt-3">
                    Don't have an account? <a href="{{ route('signup') }}" class="text">Sign Up</a>
                </p>
                <p class="has-text-centered mt-1">
                    <a href="#" class="is-size-6 text">Forgot Password?</a>
                </p>
                <p class="has-text-centered mt-1">
                    <a href="http://localhost:8080/apex/f?p=102:LOGIN_DESKTOP:28502102983520:::::" class="is-size-6 text">Admin Login</a>
                </p>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/signin.js') }}"></script>
@endpush