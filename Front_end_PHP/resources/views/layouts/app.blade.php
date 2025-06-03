<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClexoMart - @yield('title')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/headst.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footst.css') }}">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('styles')
</head>
@stack('scripts')
<body>
    <!-- Navbar -->
    <nav class="navbar" role="navigation" aria-label="main navigation">
        <div class="navbar-brand">
            <a class="navbar-item" href="{{ route('home') }}">
                <div class="image">
                    <img src="{{ asset('images/Cleckhf Shop.svg') }}" alt="Cleckhf Shop Logo">
                </div>
            </a>
            <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navbarMenu">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </a>
        </div>

        <div id="navbarMenu" class="navbar-menu">
            <div class="navbar-start">
                <div class="navbar-item">
                    <!-- Menu -->
                    <div class="dropdown-menu navbar-burger" id="dropdown-menu" role="menu">
                        <div class="dropdown-content">
                            <a href="#" class="dropdown-item">All Categories</a>
                            <a href="#" class="dropdown-item">Butchers</a>
                            <a href="#" class="dropdown-item">Greengrocer</a>
                            <a href="#" class="dropdown-item">Fishmonger</a>
                            <a href="#" class="dropdown-item">Bakery</a>
                            <a href="#" class="dropdown-item">Delicatessen</a>
                        </div>
                    </div>
                    <!-- Search Bar with Dropdown -->
                    <div class="container mt-5">
                        <div class="field has-addons">
                            <!-- Dropdown for Categories -->
                            <div class="control">
                                <div class="dropdown is-hoverable">
                                    <div class="dropdown-trigger">
                                        <button class="button" aria-haspopup="true" aria-controls="dropdown-menu">
                                            <span class="icon">
                                                <i class="fas fa-bars"></i>
                                            </span>
                                        </button>
                                    </div>
                                    <div class="dropdown-menu" id="dropdown-menu" role="menu">
                                        <div class="dropdown-content">
                                            <a href="#" class="dropdown-item">All Categories</a>
                                            <a href="#" class="dropdown-item">Butchers</a>
                                            <a href="#" class="dropdown-item">Greengrocer</a>
                                            <a href="#" class="dropdown-item">Fishmonger</a>
                                            <a href="#" class="dropdown-item">Bakery</a>
                                            <a href="#" class="dropdown-item">Delicatessen</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Search Input -->
                            <div class="control is-expanded">
                                <input class="input" type="text" placeholder="Hinted search text">
                            </div>
                            <!-- Search Button -->
                            <div class="control">
                                <button class="button">
                                    <span class="icon">
                                        <i class="fas fa-search"></i>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="navbar-end">
                <div class="navbar-item">
                    <div class="buttons">
                        @if(session('user_id'))
                            <!-- Logged-in User Navigation -->
                            <a class="button is-rounded nav-button" href="{{ route('profile') }}">
                                <span class="icon">
                                    <i class="fas fa-user"></i> <!-- Customer Icon -->
                                </span>
                                
                            </a>
                            <a class="button is-rounded nav-button" id="logout-link">Logout</a>
                            <a class="button is-light">
                                <span class="icon">
                                    <i class="fas fa-heart"></i>
                                </span>
                            </a>
                            <a class="button is-light" href="{{ route('cart') }}">
                                <span class="icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </span>
                            </a>
                        @else
                            <!-- Guest Navigation -->
                            <a class="button is-rounded nav-button" href="{{ route('signin') }}">Sign In</a>
                            <a class="button is-rounded nav-button" href="{{ route('signup') }}">Sign Up</a>
                            <a class="button is-light">
                                <span class="icon">
                                    <i class="fas fa-heart"></i>
                                </span>
                            </a>
                            <a class="button is-light" href="{{ route('cart') }}">
                                <span class="icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer custom-footer">
        <div class="container">
            <div class="column content has-text-centered is-gapless">
                <p class="title is-4 footer-brand">ClexoMart <br>
                    <span class="line">The ultimate store for all your needs.</span></p>
            </div>
            <div class="columns is-multiline is-justify-content-space-between">
                <!-- Quick Links -->
                <div class="column is-half-tablet is-one-quarter-desktop">
                    <p class="has-text-weight-bold mb-3">Quick Links</p>
                    <ul>
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li><a href="{{ route('home') }}">Shop</a></li>
                        <li><a href="#">About</a></li>
                        <li><a href="{{ route('contact') }}">Contact</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="column is-half-tablet is-one-quarter-desktop">
                    <p class="has-text-weight-bold mb-3">Contact</p>
                    <p><span class="footer-label">Email:</span> <a href="mailto:support@clexomart.com" class="footer-email">support@clexomart.com</a></p>
                    <p><span class="footer-label">Phone:</span> +44-9800000000</p>
                    <p><span class="footer-label">Address:</span> Clekhuddersfax, UK</p>
                </div>

                <!-- Social Media -->
                <div class="column is-half-tablet is-one-quarter-desktop">
                    <p class="has-text-weight-bold mb-3">Follow Us</p>
                    <div class="social-buttons">
                        <a href="https://facebook.com" target="_blank">
                            <span class="icon"><i class="fab fa-facebook icon_facebook"></i></span>
                        </a>
                        <a href="https://x.com" target="_blank">
                            <span class="icon"><i class="fab fa-twitter icon_twitter"></i></span>
                        </a>
                        <a href="https://instagram.com" target="_blank">
                            <span class="icon"><i class="fab fa-instagram icon_instagram"></i></span>
                        </a>
                        <a href="https://linkedin.com" target="_blank">
                            <span class="icon"><i class="fab fa-linkedin icon_linkedin"></i></span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="content has-text-centered is-size-7">
                <p>© 2025 ClexoMart. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="{{ asset('js/script.js') }}"></script>
    <!-- Include SweetAlert2 JS for logout confirmation -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Logout functionality
        document.getElementById('logout-link').addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: 'You will be logged out.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, log out!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Perform logout by clearing session and redirecting
                    fetch('/logout', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = '{{ route('home') }}';
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', 'Failed to log out.', 'error');
                    });
                }
            });
        });

        // Navbar burger toggle
        document.addEventListener('DOMContentLoaded', () => {
            const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
            $navbarBurgers.forEach(el => {
                el.addEventListener('click', () => {
                    const target = el.dataset.target;
                    const $target = document.getElementById(target);
                    el.classList.toggle('is-active');
                    $target.classList.toggle('is-active');
                });
            });
        });
    </script>
</body>
</html>