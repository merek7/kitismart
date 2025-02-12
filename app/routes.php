<?php
return [
    ['GET', '/', 'HomeController#index', 'home'],
    ['GET', '/dashboard', 'HomeController#dashboard', 'dashboard'],
    ['GET', '/register', 'RegisterController#showRegisterForm', 'register_form'],
    ['POST', '/register', 'RegisterController#register', 'register_submit'],
    ['GET', '/login', 'LoginController#showLoginForm', 'login_form'],
    ['POST', '/login', 'LoginController#login', 'login_submit'],
    ['GET', '/logout', 'AuthController#logout', 'logout'],
    ['GET', '/confirmation/[*:token]', 'ConfirmationController#confirm', 'confirmation'],
    ['GET', '/forgot-password', 'PasswordController#showForgotForm', 'forgot_password'],
    ['POST', '/forgot-password', 'PasswordController#sendResetLink', 'forgot_password_submit'],
    ['GET', '/reset-password/[*:token]', 'PasswordController#showResetForm', 'reset_password'],
    ['POST', '/reset-password', 'PasswordController#reset', 'reset_password_submit']
];