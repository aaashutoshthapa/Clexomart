<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Cleckhf Shop</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/headst.css">
    <link rel="stylesheet" href="css/footst.css">
    <style>
        body {
            background-color: #FAF4F2;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            color: #333333;
        }
        .navbar {
            background-color: #A8C686;
        }
        .navbar-brand {
            color: #333333;
        }
        .navbar-brand:hover {
            color: #F0355E;
        }
        .navbar-burger {
            color: #333333;
        }
        .navbar-burger:hover {
            color: #F0355E;
        }
        .navbar-menu {
            background-color: #A8C686;
        }
        .navbar-item, .navbar-link {
            color: #333333;
        }
        .navbar-item:hover, .navbar-link:hover {
            color: #F0355E !important;
            background-color: transparent;
        }
        .dropdown-item {
            color: #333333;
        }
        .dropdown-item:hover {
            background-color: #A8C686;
            color: #FFFFFF;
        }
        .button {
            background-color: #FED549;
            color: #333333;
        }
        .button:hover {
            background-color: #FBC02D;
        }
        .signup-box {
            background-color: #F5F5F5;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            margin: 2rem auto;
            color: #333333;
            text-align: center;
        }
        .title {
            font-size: 2rem;
            font-weight: bold;
            color: #333333;
            margin-bottom: 1.5rem;
        }
        .field label {
            color: #333333;
            font-weight: bold;
        }
        .select select {
            width: 100%;
        }
        .button.is-primary {
            background-color: #F26D84;
            color: #FFFFFF;
            width: 100%;
            margin-top: 1rem;
            transition: background-color 0.3s;
        }
        .button.is-primary:hover {
            background-color: #8BAF6C;
        }
        .error-message {
            color: #F26D84;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }
        .signup-link {
            display: block;
            margin-top: 1rem;
            color: #333333;
            text-decoration: none;
        }
        .signup-link:hover {
            color: #F0355E;
            text-decoration: underline;
        }
        .footer {
            background-color: #A8C686;
            color: #333333;
        }
        .footer a {
            color: #F26D84;
        }
        .footer a:hover {
            color: #F0355E;
        }
        .footer-brand {
            color: #333333;
        }
        .line {
            color: #4D4D4D;
        }
        .has-text-weight-bold {
            color: #333333;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar" role="navigation" aria-label="main navigation">
        <div class="navbar-brand">
            <a class="navbar-item" href="index.html">
                <div class="image">
                    <img src="images/Cleckhf Shop.svg" alt="Cleckhf Shop Logo">
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
                    <!--menu-->
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
                        <a class="button is-rounded" style="background-color: #FED549; color: #333333;" href="signin.html">Sign In</a>
                        <a class="button is-rounded" style="background-color: #FED549; color: #333333;">Sign Up</a>
                        <a class="button is-light">
                            <span class="icon">
                                <i class="fas fa-heart"></i>
                            </span>
                        </a>
                        <a class="button is-light">
                            <span class="icon">
                                <i class="fas fa-shopping-cart"></i>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    @if(session('success'))
        <script>
            alert("{{ session('success') }}");
        </script>
    @endif


    @if($errors->any())
        <ul style="color: red;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <!-- Sign Up Form -->
    <div class="signup-box">
        <form action="/user-form" method="POST">
    <h1 class="title">Welcome</h1>

    <!-- CSRF Token for Laravel -->
    @csrf

    <div class="field">
        <label class="label">First Name</label>
        <div class="control">
            <input name="first_name" class="input" type="text" placeholder="First Name" required>
        </div>
    </div>

    <div class="field">
        <label class="label">Last Name</label>
        <div class="control">
            <input name="last_name" class="input" type="text" placeholder="Last Name" required>
        </div>
    </div>

    <div class="field">
        <label class="label">Email</label>
        <div class="control">
            <input name="email" class="input" type="email" placeholder="Email" required>
        </div>
    </div>

    <div class="field">
        <label class="label">Contact No</label>
        <div class="control">
            <input name="contact_no" class="input" type="text" placeholder="Phone Number" required>
        </div>
    </div>

    <div class="field">
        <label class="label">Password</label>
        <div class="control">
            <input name="password" class="input" type="password" placeholder="Password" required>
        </div>
    </div>

    <div class="field">
        <label class="label">Select a Role</label>
        <div class="control">
            <div class="select is-fullwidth">
                <select name="user_type" required>
                    <option value="">Select a role</option>
                    <option value="customer">Customer</option>
                    <option value="admin">Admin</option>
                    <option value="vendor">Vendor</option>
                </select>
            </div>
        </div>
    </div>

    <button type="submit" class="button is-primary">Sign up</button>
    <a href="signin.html" class="signup-link">Already have an account? Sign in</a>
</form>

    </div>

    <!-- Footer -->
    <footer class="footer custom-footer">
        <div class="container">
            <div class="column content has-text-centered is-gapless">
                <p class="title is-4 footer-brand ">ClexoMart <br>
                <span class="line">The ultimate store for all your needs.</span></p>    
            </div>
            <div class="columns is-multiline is-justify-content-space-between">
                <!-- Quick Links -->
                <div class="column is-half-tablet is-one-quarter-desktop">
                    <p class="has-text-weight-bold mb-3">Quick Links</p>
                    <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="#">Shop</a></li>
                    <li><a href="#">About</a></li>
                    <li><a href="contact.html">Contact</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="column is-half-tablet is-one-quarter-desktop">
                    <p class="has-text-weight-bold mb-3">Contact</p>
                    <p><span class="footer-label">Email:</span> <a href="mailto:support@clexomart.com" class="footer-email">support@clexomart.com</a></p>
                    <p><span class="footer-label">Phone:</span> +977-9800000000</p>
                    <p><span class="footer-label">Address:</span> Kathmandu, Nepal</p>
                </div>

                <!-- Social Media -->
                <div class="column is-half-tablet is-one-quarter-desktop">
                    <p class="has-text-weight-bold mb-3">Follow Us</p>
                    <div class="social-buttons">
                        <a href="https://facebook.com" target="_blank">
                            <span class="icon"><i class="fab fa-facebook icon_facebook"></i></span>
                        </a>
                        <a  href="https://x.com" target="_blank">
                            <span class="icon"><i class="fab fa-twitter icon_twitter"></i></span>
                        </a>
                        <a  href="https://instagram.com"  target="_blank">
                            <span class="icon"><i class="fab fa-instagram icon_instagram"></i></span>
                        </a>
                        <a  href="https://linkedin.com"  target="_blank">
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

    <script src="js/script.js"></script>
    <script>
        // Navbar burger functionality
        document.addEventListener('DOMContentLoaded', () => {
            const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
            if ($navbarBurgers.length > 0) {
                $navbarBurgers.forEach(el => {
                    el.addEventListener('click', () => {
                        const target = el.dataset.target;
                        const $target = document.getElementById(target);
                        el.classList.toggle('is-active');
                        $target.classList.toggle('is-active');
                    });
                });
            }
        });

        // Form validation
    </script>
</body>
</html>