<?php
namespace App\Config;

class PermissionConfig {
    public static array $permissions = [
        // System & Platform
        'system.manage' => 'Full platform configuration access',
        'users.manage' => 'Manage user accounts and roles',
        'organizations.manage' => 'Manage multi-tenant organizations',
        'audit_logs.view' => 'View system security audit logs',
        
        // Facility & Court Management
        'facility.view' => 'View facility details',
        'facility.create' => 'Create new facility',
        'facility.update' => 'Update facility information',
        'facility.delete' => 'Delete facility',
        
        'court.view' => 'View courts',
        'court.create' => 'Create new court',
        'court.update' => 'Update court settings',
        'court.delete' => 'Delete court',
        'availability.manage' => 'Manage court operating hours and blockouts',
        'pricing.manage' => 'Configure court pricing rules',
        
        // Bookings & Reservations
        'booking.view' => 'View bookings',
        'booking.create' => 'Create booking reservation',
        'booking.update' => 'Update booking details',
        'booking.cancel' => 'Cancel booking',
        'booking.view_own' => 'View customer own bookings',
        'booking.cancel_own' => 'Cancel customer own bookings',
        
        // Payments & Finance
        'payment.view' => 'View transaction payments',
        'payment.create' => 'Process payment',
        'payment.manage' => 'Manage refunds and payouts',
        
        // Reports
        'report.view' => 'View business and revenue analytics'
    ];

    public static array $rolePermissions = [
        'super_admin' => [
            'system.manage', 'users.manage', 'organizations.manage', 'audit_logs.view',
            'facility.view', 'facility.create', 'facility.update', 'facility.delete',
            'court.view', 'court.create', 'court.update', 'court.delete',
            'availability.manage', 'pricing.manage',
            'booking.view', 'booking.create', 'booking.update', 'booking.cancel',
            'payment.view', 'payment.create', 'payment.manage', 'report.view'
        ],
        'court_owner' => [
            'facility.view', 'facility.create', 'facility.update', 'facility.delete',
            'court.view', 'court.create', 'court.update', 'court.delete',
            'availability.manage', 'pricing.manage',
            'booking.view', 'booking.create', 'booking.update', 'booking.cancel',
            'payment.view', 'payment.create', 'report.view'
        ],
        'facility_manager' => [
            'facility.view', 'facility.update',
            'court.view', 'court.update', 'availability.manage', 'pricing.manage',
            'booking.view', 'booking.create', 'booking.update', 'booking.cancel',
            'payment.view', 'report.view'
        ],
        'receptionist' => [
            'court.view', 'booking.view', 'booking.create', 'booking.update', 'booking.cancel',
            'payment.create'
        ],
        'customer' => [
            'facility.view', 'court.view', 'booking.create', 'booking.view_own', 'booking.cancel_own',
            'payment.create'
        ]
    ];
}
