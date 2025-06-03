@extends('layouts.app')

@section('title', 'Profile')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user.css') }}">
@endpush


@section('content')

    <div class="container">
        @if (session('success'))
            <div class="notification is-success">
                {{ session('success') }}
            </div>
        @endif

<div class="columns is-gapless">
        <!-- Sidebar -->
        <div class="column is-one-fifth mt-4  p-4">
            <aside class="menu box is-shadowless">
                <p class=" menu-label">Account Settings</p>
                    <ul class="menu-list ">
                        <li><a href="{{ route('profile-edit') }}" class="edit">Edit Profile</a></li>
                        <li><a href="{{ route('profile.changepass') }}" class="edit">Change Password</a></li>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                          <a href="#" class="has-text-danger" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                              Logout
                          </a>
                      </li>
                    </ul>
            </aside>
        </div>

        <!-- Profile Content -->

            <div class="column">
               
            <section class="section">
                 <h1 class="title has-text-centered mt-2">My Details</h1>
                <div class="card profile-box">
                

                    <div class="card-content">
                        <div class="columns is-multiline">
                            <!-- Profile Image and Info -->
                            <div class="column is-one-quarter has-text-centered">
                                <figure class="image is-128x128 is-inline-block ">
                                    <img class="is-rounded"  src="{{ route('profile.image', $user->user_id) }}"  alt="Profile Picture">
                                </figure>
                            </div>

                            <!-- User Details -->
                            <div class="column is-three-quarters">
                                <table class="table is-fullwidth">
                                    <tbody>
                                        <h5 class="title is-5 mt-2">{{ ucwords($user->first_name) }} {{ ucwords($user->last_name) }}</h5>
                                        <tr >
                                            <th class="has-background-light">Email</th>
                                            <td class="has-background-light">{{ $user->email }}</td>
                                        </tr>
                                        <tr>
                                            <th class="has-background-light">Phone Number</th>
                                            <td class="has-background-light">{{ $user->contact_no }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        </div>
    </div>
@endsection
