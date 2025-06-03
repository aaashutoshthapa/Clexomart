@extends('layouts.app')

@section('title', 'Contact Us')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/contact.css') }}">
@endpush

@section('content')
    <section class="section">
        <div class="container">
            <div class="columns is-multiline is-variable is-6">
                <!-- Contact Info -->
                <div class="column is-full-mobile is-half-tablet is-two-fifths-desktop">
                    <h2 class="title is-size-3 is-size-4-mobile">Contact Us</h2>

                    <p class="mb-4">
                        <span class="icon custom-icon"><i class="fa-solid fa-envelope"></i></span>
                        <strong>Email:</strong><br>support@clexomart.com
                    </p>

                    <p class="mb-4">
                        <span class="icon custom-icon"><i class="fa-solid fa-phone"></i></span>
                        <strong>Phone:</strong><br>+01 5432167<br>+977-9800000000
                    </p>

                    <p class="mb-4">
                        <span class="icon custom-icon"><i class="fa-solid fa-location-dot"></i></span>
                        <strong>Address:</strong><br>Kathmandu, Nepal
                    </p>

                </div>

                <!-- Contact Form -->
                <div class="column is-full-mobile is-half-tablet contact-form-section">
                    <h2 class="title is-size-3 is-size-4-mobile">Send Us a Message</h2>
                    <p class="subtitle is-size-6">We'd love to hear from you!</p>

                    <form method="POST" action="{{ url('/contact') }}">
                        @csrf
                        <div class="field">
                            <label class="label">Name</label>
                            <input class="input" type="text" name="name" placeholder="Your Full Name" required>
                        </div>

                        <div class="field">
                            <label class="label">Email</label>
                            <input class="input" type="email" name="email" placeholder="Your Email Address" required>
                        </div>

                        <div class="field">
                            <label class="label">Message</label>
                            <textarea class="textarea" name="message" placeholder="Your Message.." required></textarea>
                        </div>

                        <div class="field">
                            <input type="submit" class="custom-cta-btn mt-2" value="Send Message">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/contact.js') }}"></script>
@endpush