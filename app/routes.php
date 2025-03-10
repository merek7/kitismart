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
    ['POST', '/reset-password', 'PasswordController#reset', 'reset_password_submit'],
    ['GET', '/budget/create', 'BudgetController#showCreateBudgetForm', 'create_budget_form'],
    ['POST', '/budget/create', 'BudgetController#create', 'create_budget'],
    ['GET', '/budget/active', 'BudgetController#getActiveBudget', 'get_active_budget'],
    ['GET', '/budget/[i:id]/summary', 'BudgetController#getBudgetSummary', 'budget_summary'],
    ['GET','/parametrage','ParametrageController#showCreateParametrageForm','parametrage_form'],
    ['GET','/expenses/create','ExpenseController#showCreateExpenseForm','expense_form'],
    ['POST','/expenses/create','ExpenseController#create','expense_submit'],
    ['GET', '/expenses/list', 'ExpenseController#listPaginated', 'expense_list'],
    ['POST','/expenses/mark-paid/[i:id]','ExpenseController#markAsPaid', 'expense_paid'],
    ['PUT', '/expenses/update/[i:id]', 'ExpenseController#update', 'expense_update']
];
