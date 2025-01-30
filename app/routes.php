<?php
return [
    ['GET', '/', 'HomeController#index', 'home'],
    ['GET', '/dashboard', 'HomeController#dashboard', 'dashboard'],
    ['GET', '/register', 'RegisterController#showRegisterForm', 'register_form'],
    ['POST', '/register', 'RegisterController#register', 'register_submit'],
    ['GET', '/login', 'LoginController#showLoginForm', 'login'],
    ['POST', '/login', 'LoginController#login', 'login_submit'],
    ['GET', '/logout', 'AuthController#logout', 'logout'],
];