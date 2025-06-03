@extends('layouts.app')

@section('title', 'Verify OTP')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/verify-otp.css') }}">
@endpush

@section('content')
    <section class="section">
        <div class="container">
            <div class="column is-half is-offset-one-quarter box">
                <h1 class="title has-text-centered">Verify Your Email</h1>

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

                <form method="POST" action="{{ route('verify.otp') }}">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">

                    <div class="field">
                        <label class="label">Enter OTP</label>
                        <div class="control">
                            <input class="input" type="text" name="otp" placeholder="Enter the 6-digit OTP" required>
                        </div>
                    </div>

                    <div class="field">
                        <div class="control">
                            <button type="submit" class="button is-primary is-fullwidth">Verify OTP</button>
                        </div>
                    </div>
                </form>

                <!-- Resend OTP Section -->
                <div class="has-text-centered mt-4">
                    <a href="{{ route('resend.otp') }}" onclick="event.preventDefault(); document.getElementById('resend-otp-form').submit();">Resend OTP</a>
                    <form id="resend-otp-form" method="POST" action="{{ route('resend.otp') }}" style="display: none;">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection