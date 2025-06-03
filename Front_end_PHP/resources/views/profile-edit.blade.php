@extends('layouts.app')

@section('title', 'Edit Profile')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user.css') }}">
@endpush

@section('content')
<section class="section">
  <div class="container">
    <h1 class="title">Edit Profile</h1>

    @if ($errors->any())
      <div class="notification is-danger">
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
      @csrf

     
      <div class="field mb-2">
        <label class="label">First Name</label>
        <div class="control">
          <input class="input" type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
        </div>
      </div>

      <div class="field mt-2">
        <label class="label">Last Name</label>
        <div class="control">
          <input class="input" type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
        </div>
      </div>

      <div class="field mt-2">
        <label class="label">Contact Number</label>
        <div class="control">
          <input class="input" type="text" name="contact_no" value="{{ old('contact_no', $user->contact_no) }}" required>
        </div>
      </div>

      <div class="field mt-2 mb-2">
        <label class="label">Email Address</label>
            <div class="control ">
              <input class="input" type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>
      </div>

     <div class="field">
        <label class="label">Profile Image</label>
        <div class="control">
            <input type="file" name="profile_image" class="input">
        </div>
    </div>
    
      <div class="field is-grouped  mt-1">
        <div class="control">
          <button class="button edit-button ml-2" type="submit">Save Changes</button>
        </div>
        <div class="control">
          <a class="button edit-button ml-2" href="{{ route('profile') }}">Cancel</a>
        </div>


      </div>
    </form>
  </div>
</section>
@endsection
