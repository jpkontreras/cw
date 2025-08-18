<?php

return [
    /**
     * Staff Module Feature Flags
     * These flags control which features are enabled in the staff management system.
     */
    
    // Clock-in/out methods
    'staff.biometric_clock' => env('FEATURE_STAFF_BIOMETRIC_CLOCK', false),
    'staff.mobile_clock' => env('FEATURE_STAFF_MOBILE_CLOCK', true),
    'staff.facial_recognition' => env('FEATURE_STAFF_FACIAL_RECOGNITION', false),
    'staff.geolocation_verification' => env('FEATURE_STAFF_GEOLOCATION', true),
    
    // Shift management
    'staff.shift_swapping' => env('FEATURE_STAFF_SHIFT_SWAPPING', true),
    'staff.shift_templates' => env('FEATURE_STAFF_SHIFT_TEMPLATES', true),
    'staff.auto_scheduling' => env('FEATURE_STAFF_AUTO_SCHEDULING', false),
    'staff.shift_bidding' => env('FEATURE_STAFF_SHIFT_BIDDING', false),
    
    // Performance & Training
    'staff.performance_tracking' => env('FEATURE_STAFF_PERFORMANCE_TRACKING', false),
    'staff.training_modules' => env('FEATURE_STAFF_TRAINING_MODULES', false),
    'staff.skill_assessments' => env('FEATURE_STAFF_SKILL_ASSESSMENTS', false),
    'staff.goal_setting' => env('FEATURE_STAFF_GOAL_SETTING', false),
    
    // Payroll & Finance
    'staff.payroll_integration' => env('FEATURE_STAFF_PAYROLL_INTEGRATION', false),
    'staff.tip_management' => env('FEATURE_STAFF_TIP_MANAGEMENT', true),
    'staff.commission_tracking' => env('FEATURE_STAFF_COMMISSION_TRACKING', false),
    'staff.expense_claims' => env('FEATURE_STAFF_EXPENSE_CLAIMS', false),
    
    // Communication
    'staff.announcements' => env('FEATURE_STAFF_ANNOUNCEMENTS', true),
    'staff.messaging' => env('FEATURE_STAFF_MESSAGING', false),
    'staff.push_notifications' => env('FEATURE_STAFF_PUSH_NOTIFICATIONS', true),
    
    // Leave Management
    'staff.leave_requests' => env('FEATURE_STAFF_LEAVE_REQUESTS', true),
    'staff.sick_leave_tracking' => env('FEATURE_STAFF_SICK_LEAVE', true),
    'staff.vacation_planning' => env('FEATURE_STAFF_VACATION_PLANNING', true),
    
    // Advanced Features
    'staff.multi_location' => env('FEATURE_STAFF_MULTI_LOCATION', true),
    'staff.workforce_analytics' => env('FEATURE_STAFF_ANALYTICS', false),
    'staff.ai_scheduling' => env('FEATURE_STAFF_AI_SCHEDULING', false),
    'staff.compliance_tracking' => env('FEATURE_STAFF_COMPLIANCE', true),
    
    // Document Management
    'staff.document_upload' => env('FEATURE_STAFF_DOCUMENT_UPLOAD', true),
    'staff.contract_management' => env('FEATURE_STAFF_CONTRACT_MANAGEMENT', true),
    'staff.certification_tracking' => env('FEATURE_STAFF_CERTIFICATIONS', true),
    
    // Reports
    'staff.attendance_reports' => env('FEATURE_STAFF_ATTENDANCE_REPORTS', true),
    'staff.payroll_reports' => env('FEATURE_STAFF_PAYROLL_REPORTS', false),
    'staff.productivity_reports' => env('FEATURE_STAFF_PRODUCTIVITY_REPORTS', false),
    'staff.custom_reports' => env('FEATURE_STAFF_CUSTOM_REPORTS', false),
];