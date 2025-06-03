@extends('layouts.app')

@section('title', 'Change Password')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user.css') }}">
@endpush

@section('content')
<section class="section">
  <div class="container">
    <h1 class="title">Change Password</h1>

    @if ($errors->any())
      <div class="notification is-danger">
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('profile.change-password') }}" method="POST">
      @csrf
      
      <div class="field">
        <label class="label">Current Password</label>
        <div class="control">
          <input class="input" type="password" name="current_password" required>
        </div>
      </div>

      <div class="field">
        <label class="label">New Password</label>
        <div class="control">
          <input class="input" type="password" name="new_password" required>
        </div>
      </div>

      <div class="field">
        <label class="label">Confirm New Password</label>
        <div class="control">
          <input class="input" type="password" name="new_password_confirmation" required>
        </div>
      </div>

      <div class="field is-grouped">
        <div class="control">
          <button class="button edit-button" type="submit">Change Password</button>
        </div>
        <div class="control">
          <a class="button edit-button" href="{{ route('profile') }}">Cancel</a>
        </div>
      </div>
    </form>
  </div>
</section>
@endsection
