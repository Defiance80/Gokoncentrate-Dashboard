<?php

return [
    // Page titles
    'settings_title' => 'Token Settings',
    'users_title' => 'Token Users',
    'user_details_title' => 'User Token Details',

    // Section headers
    'section_display' => 'Display Settings',
    'section_control' => 'Control Settings',
    'section_valuation' => 'Valuation Settings',
    'section_earning' => 'Earning Settings',
    'section_actions' => 'Admin Actions',
    'section_ledger' => 'Transaction Ledger',

    // Settings labels
    'lbl_token_label' => 'Token Label',
    'lbl_token_name' => 'Token Name',
    'lbl_usd_cents_per_token' => 'USD Cents per Token',
    'lbl_earn_cents' => 'Earn Amount (cents)',
    'lbl_earn_seconds' => 'Earn Interval (seconds)',
    'lbl_daily_cap' => 'Daily Cap (cents)',
    'lbl_global_enabled' => 'Token System Enabled',
    'lbl_repeat_cooldown' => 'Repeat Cooldown (seconds)',
    'lbl_eligible_content' => 'Eligible Content Types',

    // Help text
    'help_token_label' => 'Short label shown in UI (e.g., KT)',
    'help_global_enabled' => 'Master switch to enable/disable token earning globally',
    'help_daily_cap' => 'Maximum tokens a user can earn per day (in cents)',
    'help_repeat_cooldown' => 'Cooldown before same content can earn again',
    'help_usd_cents' => '100 = $1.00 per token',
    'help_earn_rate' => 'Users earn this many cents per interval of seconds',

    // Content flags
    'flag_free_video' => 'Free Video',
    'flag_free_magazine' => 'Free Magazine',
    'flag_focus_mode' => 'Focus Mode',

    // User list labels
    'lbl_user' => 'User',
    'lbl_balance' => 'Balance',
    'lbl_earning_status' => 'Earning Status',
    'lbl_last_earned' => 'Last Earned',
    'lbl_last_spent' => 'Last Spent',

    // User details labels
    'lbl_current_balance' => 'Current Balance',
    'lbl_lifetime_earned' => 'Lifetime Earned',
    'lbl_lifetime_spent' => 'Lifetime Spent',
    'lbl_new_balance' => 'New Balance',

    // Ledger labels
    'lbl_date' => 'Date',
    'lbl_type' => 'Type',
    'lbl_amount' => 'Amount',
    'lbl_source' => 'Source',
    'lbl_reason' => 'Reason',
    'lbl_admin' => 'Admin',
    'lbl_balance_after' => 'Balance After',

    // Actions
    'btn_add_tokens' => 'Add Tokens',
    'btn_deduct_tokens' => 'Deduct Tokens',
    'btn_set_balance' => 'Set Balance',
    'btn_suspend' => 'Suspend Earning',
    'btn_unsuspend' => 'Unsuspend Earning',
    'btn_view_details' => 'View Details',

    // Messages
    'settings_saved' => 'Token settings saved successfully.',
    'tokens_added' => 'Tokens added successfully.',
    'tokens_deducted' => 'Tokens deducted successfully.',
    'balance_set' => 'Balance set successfully.',
    'earning_suspended' => 'User earning suspended.',
    'earning_unsuspended' => 'User earning unsuspended.',
    'insufficient_balance' => 'Insufficient balance for this deduction.',
    'permission_denied' => 'Permission denied.',
    'user_details' => 'User details retrieved successfully.',
    'no_transactions' => 'No transactions found.',

    // Warnings
    'deduct_warning' => 'This action will reduce the user\'s token balance.',
    'set_balance_warning' => 'This will override the user\'s current balance. Use with caution.',
    'max_deduct' => 'Maximum deduction',

    // Confirmations
    'confirm_unsuspend' => 'Are you sure you want to unsuspend earning for this user?',

    // Status
    'status_active' => 'Active',
    'status_suspended' => 'Suspended',
    'super_admin_only' => 'Super Admin Only',

    // Transaction types
    'type_earned' => 'Earned',
    'type_spent' => 'Spent',
    'type_admin_credit' => 'Admin Credit',
    'type_admin_debit' => 'Admin Debit',
    'type_admin_set' => 'Admin Set',
    'type_refund' => 'Refund',

    // Validation messages
    'validation' => [
        'token_label_required' => 'Token label is required.',
        'token_name_required' => 'Token name is required.',
        'earn_seconds_min' => 'Earn interval must be at least 1 second.',
        'amount_required' => 'Amount is required.',
        'amount_min' => 'Amount must be greater than 0.',
        'reason_max' => 'Reason cannot exceed 255 characters.',
    ],
];
