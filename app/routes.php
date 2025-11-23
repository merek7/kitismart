<?php

return [
    ['GET', '/', 'HomeController#index', 'home'],
    ['GET', '/dashboard', 'HomeController#dashboard', 'dashboard'],
    ['GET', '/register', 'RegisterController#showRegisterForm', 'register_form'],
    ['POST', '/register', 'RegisterController#register', 'register_submit'],
    ['GET', '/login', 'LoginController#showLoginForm', 'login_form'],
    ['POST', '/login', 'LoginController#login', 'login_submit'],
    ['GET', '/logout', 'AuthController#logout', 'logout'],

    // Google OAuth Routes
    ['GET', '/auth/google', 'GoogleAuthController#redirectToGoogle', 'google_auth'],
    ['GET', '/auth/google/callback', 'GoogleAuthController#callback', 'google_callback'],
    ['GET', '/confirmation/[*:token]', 'ConfirmationController#confirm', 'confirmation'],
    ['GET', '/forgot-password', 'PasswordController#showForgotForm', 'forgot_password'],
    ['POST', '/forgot-password', 'PasswordController#sendResetLink', 'forgot_password_submit'],
    ['GET', '/reset-password/[*:token]', 'PasswordController#showResetForm', 'reset_password'],
    ['POST', '/reset-password', 'PasswordController#reset', 'reset_password_submit'],
    ['GET', '/budget/create', 'BudgetController#showCreateBudgetForm', 'create_budget_form'],
    ['POST', '/budget/create', 'BudgetController#create', 'create_budget'],
    ['GET', '/budget/active', 'BudgetController#getActiveBudget', 'get_active_budget'],
    ['GET', '/budget/[i:id]/summary', 'BudgetController#getBudgetSummary', 'budget_summary'],
    ['GET', '/budgets/history', 'BudgetHistoryController#index', 'budget_history'],
    ['GET', '/budgets/[i:id]', 'BudgetHistoryController#show', 'budget_show'],
    ['GET', '/budgets/history/data', 'BudgetHistoryController#getData', 'budget_history_data'],
    ['GET', '/budgets/history/export', 'BudgetHistoryController#exportCsv', 'budget_history_export'],
    ['GET', '/budgets/history/export-pdf', 'BudgetHistoryController#exportPdf', 'budget_history_export_pdf'],
    ['GET', '/budgets/[i:id]/export-pdf', 'BudgetHistoryController#exportBudgetPdf', 'budget_detail_export_pdf'],
    ['GET','/expenses/create','ExpenseController#showCreateExpenseForm','expense_form'],
    ['POST','/expenses/create','ExpenseController#create','expense_submit'],
    ['GET', '/expenses/list', 'ExpenseController#listPaginated', 'expense_list'],
    ['POST','/expenses/mark-paid/[i:id]','ExpenseController#markAsPaid', 'expense_paid'],
    ['PUT', '/expenses/update/[i:id]', 'ExpenseController#update', 'expense_update'],
    ['DELETE', '/expenses/delete/[i:id]', 'ExpenseController#delete', 'expense_delete'],
    ['GET', '/expenses/export/csv', 'ExportController#exportCsv', 'export_csv'],
    ['GET', '/expenses/export/pdf', 'ExportController#exportPdf', 'export_pdf'],
    ['GET', '/expenses/recurrences', 'RecurrenceController#index', 'recurrences_list'],
    ['POST', '/expenses/recurrences/create', 'RecurrenceController#create', 'recurrence_create'],
    ['POST', '/expenses/recurrences/toggle/[i:id]', 'RecurrenceController#toggle', 'recurrence_toggle'],
    ['POST', '/expenses/recurrences/update/[i:id]', 'RecurrenceController#update', 'recurrence_update'],
    ['POST', '/expenses/recurrences/delete/[i:id]', 'RecurrenceController#delete', 'recurrence_delete'],
    ['GET', '/categories', 'CategoryController#index', 'categories_list'],
    ['GET', '/categories/create', 'CategoryController#showCreateForm', 'category_create_form'],
    ['POST', '/categories/create', 'CategoryController#create', 'category_create'],
    ['GET', '/categories/[i:id]/edit', 'CategoryController#edit', 'category_edit'],
    ['PUT', '/categories/[i:id]', 'CategoryController#update', 'category_update'],
    ['DELETE', '/categories/[i:id]', 'CategoryController#delete', 'category_delete'],
    ['GET', '/categories/all', 'CategoryController#getAll', 'categories_get_all'],
    ['GET', '/notifications/settings', 'NotificationController#index', 'notifications_settings'],
    ['POST', '/notifications/settings', 'NotificationController#update', 'notifications_update'],
    ['GET', '/settings', 'SettingsController#index', 'settings'],
    ['POST', '/settings/update-profile', 'SettingsController#updateProfile', 'settings_update_profile'],
    ['POST', '/settings/update-password', 'SettingsController#updatePassword', 'settings_update_password'],
    ['POST', '/settings/delete-account', 'SettingsController#deleteAccount', 'settings_delete_account'],

    // Budget Sharing Routes
    ['GET', '/budget/[i:id]/share', 'BudgetShareController#showShareForm', 'budget_share_form'],
    ['POST', '/budget/[i:id]/share', 'BudgetShareController#createShare', 'budget_share_create'],
    ['GET', '/budget/shares/manage', 'BudgetShareController#manageShares', 'budget_shares_manage'],
    ['POST', '/budget/shares/[i:id]/revoke', 'BudgetShareController#revokeShare', 'budget_share_revoke'],

    // Guest Access Routes - IMPORTANT: routes spécifiques AVANT les wildcards
    ['GET', '/budget/shared/dashboard', 'BudgetShareController#guestDashboard', 'budget_guest_dashboard'],
    ['POST', '/budget/shared/expense/create', 'BudgetShareController#guestCreateExpense', 'budget_guest_expense_create'],
    ['GET', '/budget/shared/logout', 'BudgetShareController#guestLogout', 'budget_guest_logout'],
    ['GET', '/budget/shared/[*:token]', 'BudgetShareController#showGuestAccess', 'budget_guest_access'],
    ['POST', '/budget/shared/[*:token]/authenticate', 'BudgetShareController#authenticateGuest', 'budget_guest_auth'],

    // Onboarding API Routes
    ['POST', '/api/onboarding/complete/[*:step]', 'OnboardingController#completeStep', 'onboarding_complete'],
    ['POST', '/api/onboarding/skip/[*:step]', 'OnboardingController#skipStep', 'onboarding_skip'],
    ['GET', '/api/onboarding/status', 'OnboardingController#getStatus', 'onboarding_status'],
    ['POST', '/api/onboarding/reset', 'OnboardingController#reset', 'onboarding_reset'],

    // API utilitaires
    ['GET', '/api/csrf-token', 'ApiController#getCsrfToken', 'api_csrf_token'],

    // Admin - Test des emails (DEV ONLY)
    ['GET', '/admin/email-test', 'EmailTestController#index', 'email_test'],
    ['POST', '/admin/email-test/send', 'EmailTestController#sendTest', 'email_test_send'],
    ['GET', '/admin/email-test/preview', 'EmailTestController#preview', 'email_test_preview']
];
