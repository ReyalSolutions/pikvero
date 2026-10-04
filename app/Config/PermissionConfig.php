<?php
namespace App\Config;

class PermissionConfig {
    public static array $permissions = [
        // System & Admin Users (Separate Permissions)
        'system.manage' => 'Full platform configuration and system management',
        'users.manage' => 'Full control to create, edit, suspend, reset, and delete user accounts',
        'users.view' => 'View user account directory and details',
        'users.create' => 'Create new user accounts',
        'users.edit' => 'Edit user profile and account details',
        'users.delete' => 'Soft-delete user accounts from the system',
        'users.suspend' => 'Suspend or reactivate user accounts',
        'users.reset_password' => 'Reset user account passwords',
        'roles.manage' => 'Manage system roles',
        'permissions.manage' => 'Manage permission assignments',
        'settings.manage' => 'Manage global system settings',
        'audit_logs.view' => 'View security audit logs',
        'audit_logs.delete' => 'Delete or purge security audit logs',
        'audit_logs.export' => 'Export security audit log reports to CSV',
        'audit_logs.manage' => 'Manage security audit log retention policies',
        
        // Organization & Multi-Tenant
        'organizations.manage' => 'Manage platform multi-tenant organizations',
        'organizations.view' => 'View organization profiles',
        'organizations.verify' => 'Verify and approve tenant organizations',
        'organization.view' => 'View tenant organization profile',
        'organization.update' => 'Update tenant organization settings',
        
        // Facilities
        'facilities.manage' => 'Platform level facility management',
        'facilities.view' => 'View facilities list',
        'facilities.verify' => 'Verify facility applications',
        'facility.view' => 'View facility details',
        'facility.create' => 'Create new facility',
        'facility.update' => 'Update facility details',
        'facility.delete' => 'Delete facility',
        
        // Courts
        'courts.manage' => 'Platform level court management',
        'courts.view' => 'View court directory',
        'court.view' => 'View court details',
        'court.create' => 'Add new court',
        'court.update' => 'Edit court details',
        'court.delete' => 'Delete court',
        'court.availability.view' => 'View court time slot availability',
        'availability.manage' => 'Manage operating hours and schedule blockouts',
        'pricing.manage' => 'Configure court rates and pricing rules',
        
        // Bookings & Reservations
        'bookings.manage' => 'Platform level booking management',
        'bookings.view' => 'View all platform bookings',
        'booking.view' => 'View facility bookings',
        'booking.create' => 'Create new court reservation',
        'booking.update' => 'Edit booking reservation',
        'booking.cancel' => 'Cancel booking reservation',
        'booking.export' => 'Export court booking and reservation reports',
        'booking.print' => 'Print court reservation receipt and summary vouchers',
        'booking.view_own' => 'View own customer bookings',
        'booking.cancel_own' => 'Cancel own customer booking',
        'checkin.manage' => 'Process player check-in',
        
        // Payments & Subscriptions
        'payments.manage' => 'Manage payments and transactions',
        'payments.view' => 'View payment transactions',
        'payment.view' => 'View facility payments',
        'payment.create' => 'Process payment transaction',
        'payment.refund' => 'Process payment refund',
        'payment.view_own' => 'View own payment history',
        'invoices.view' => 'View invoices',
        'payouts.view' => 'View GCash payout requests and metrics',
        'payouts.request' => 'Submit GCash payout requests for court bookings',
        'payouts.manage' => 'Approve, reject, or complete GCash payout requests',
        'subscriptions.manage' => 'Manage platform subscriptions',
        'subscriptions.view' => 'View subscription plans',
        'subscription.view' => 'View tenant subscription',
        'subscription.manage' => 'Manage tenant subscription plan',
        'plans.manage' => 'Manage subscription tiers and pricing',
        'subscriptionplans.view' => 'View subscription plans and tiers',
        'subscriptionplans.manage' => 'Manage and edit subscription plans and tiers',
        'subscriptionhistory.view' => 'View subscription payment history',
        
        // Customers & Staff Management
        'customer.view' => 'View customer details',
        'customer.create' => 'Register new customer',
        'customers.view' => 'View system customer list',
        'staff.view' => 'View facility staff',
        'staff.create' => 'Add facility staff member',
        'staff.update' => 'Edit facility staff member',
        'staff.remove' => 'Remove facility staff member',
        
        // Marketing, Reviews & Packages
        'package.manage' => 'Manage hour packages',
        'promotion.manage' => 'Manage promotional discount codes',
        'review.view' => 'View court reviews',
        'review.respond' => 'Respond to customer reviews',
        'review.create' => 'Submit court review',
        'favorite.manage' => 'Manage favorite courts',
        'profile.manage' => 'Manage user profile',
        
        // Reports, Support & Disputes
        'reports.view' => 'View analytics and business reports',
        'reports.financial' => 'View financial reports',
        'support.create' => 'Create support tickets',
        'support.manage' => 'Manage customer support requests',
        'disputes.view' => 'View payment disputes',
        'disputes.manage' => 'Resolve payment disputes',

        // Amenities
        'amenities.view' => 'View amenities directory and assigned facilities',
        'amenities.manage' => 'Create, edit, delete amenities and assign to facilities',

        // Open Play Management
        'open_play.view' => 'View open play sessions and player rosters',
        'open_play.create' => 'Create new open play sessions',
        'open_play.edit' => 'Edit open play session details and capacity',
        'open_play.cancel' => 'Cancel open play sessions',
        'open_play.manage_players' => 'Register and manage open play players',
        'open_play.checkin' => 'Check-in open play attendees',
        'open_play.revenue' => 'View open play attendance and revenue reports',

        // Products & Equipment
        'products.view' => 'View products directory and stock levels',
        'products.add' => 'Add new products and rental gear',
        'products.edit' => 'Edit product details, pricing, and category',
        'products.delete' => 'Delete or archive products',
        'products.inventory' => 'Restock and manage product inventory',
        'products.sell' => 'Process POS product sales and rentals',
        'products.revenue' => 'View product sales revenue reports',

        // Schedule Calendar
        'calendar.view' => 'View court schedule and open play visual calendar',
        'calendar.manage' => 'Manage court calendar schedules'
    ];

    public static array $rolePermissions = [
        'super_admin' => [
            'system.manage', 'users.manage', 'roles.manage', 'permissions.manage',
            'organizations.manage', 'facilities.manage', 'courts.manage', 'amenities.view', 'amenities.manage', 'bookings.manage',
            'payments.manage', 'payouts.view', 'payouts.request', 'payouts.manage', 'subscriptions.manage', 'plans.manage', 'subscriptionplans.view', 'subscriptionplans.manage', 'subscriptionhistory.view', 'reports.view',
            'open_play.view', 'open_play.create', 'open_play.edit', 'open_play.cancel', 'open_play.manage_players', 'open_play.checkin', 'open_play.revenue',
            'products.view', 'products.add', 'products.edit', 'products.delete', 'products.inventory', 'products.sell', 'products.revenue',
            'calendar.view', 'calendar.manage',
            'audit_logs.view', 'audit_logs.delete', 'audit_logs.export', 'audit_logs.manage', 'settings.manage'
        ],
        'platform_admin' => [
            'users.view', 'users.create', 'users.edit', 'users.reset_password', 'users.suspend', 'users.delete', 'organizations.view', 'organizations.verify', 'facilities.view',
            'facilities.verify', 'amenities.view', 'amenities.manage', 'bookings.view', 'payments.view', 'payouts.view', 'payouts.manage', 'subscriptions.view', 'subscriptionplans.view', 'subscriptionhistory.view',
            'open_play.view', 'open_play.create', 'open_play.edit', 'open_play.cancel', 'open_play.manage_players', 'open_play.checkin', 'open_play.revenue',
            'products.view', 'products.add', 'products.edit', 'products.delete', 'products.inventory', 'products.sell', 'products.revenue',
            'calendar.view', 'calendar.manage',
            'reports.view', 'support.manage', 'audit_logs.view', 'audit_logs.export'
        ],
        'finance_admin' => [
            'payments.view', 'payments.manage', 'payment.refund', 'subscriptions.view',
            'subscription.manage', 'subscriptionplans.view', 'subscriptionhistory.view', 'invoices.view', 'reports.financial', 'payouts.view', 'payouts.manage',
            'open_play.revenue', 'products.revenue'
        ],
        'support_staff' => [
            'customers.view', 'organizations.view', 'bookings.view', 'support.create',
            'support.manage', 'disputes.view', 'disputes.manage', 'open_play.view', 'products.view', 'calendar.view'
        ],
        'court_owner' => [
            'organization.view', 'organization.update', 'facility.create', 'facility.view',
            'facility.update', 'facility.delete', 'court.create', 'court.view',
            'court.update', 'court.delete', 'amenities.view', 'amenities.manage', 'availability.manage', 'pricing.manage',
            'booking.view', 'booking.create', 'booking.update', 'booking.cancel', 'booking.export', 'booking.print', 'payment.refund',
            'users.view', 'users.create', 'users.edit', 'users.reset_password', 'users.suspend', 'users.delete', 'users.manage', 'customer.view',
            'package.manage', 'promotion.manage', 'review.view', 'review.respond',
            'open_play.view', 'open_play.create', 'open_play.edit', 'open_play.cancel', 'open_play.manage_players', 'open_play.checkin', 'open_play.revenue',
            'products.view', 'products.add', 'products.edit', 'products.delete', 'products.inventory', 'products.sell', 'products.revenue',
            'calendar.view', 'calendar.manage',
            'report.view', 'payouts.view', 'payouts.request', 'subscription.view', 'subscription.manage', 'subscriptionplans.view', 'subscriptionplans.manage', 'subscriptionhistory.view'
        ],
        'facility_manager' => [
            'facility.view', 'facility.update', 'court.view', 'court.update', 'amenities.view', 'amenities.manage',
            'availability.manage', 'pricing.manage', 'booking.view', 'booking.create',
            'booking.update', 'booking.cancel', 'booking.export', 'booking.print', 'payment.refund', 'customer.view', 'staff.view',
            'open_play.view', 'open_play.create', 'open_play.edit', 'open_play.cancel', 'open_play.manage_players', 'open_play.checkin', 'open_play.revenue',
            'products.view', 'products.add', 'products.edit', 'products.delete', 'products.inventory', 'products.sell', 'products.revenue',
            'calendar.view', 'calendar.manage',
            'package.manage', 'promotion.manage', 'report.view'
        ],
        'receptionist' => [
            'booking.view', 'booking.create', 'booking.update', 'booking.cancel', 'booking.export', 'booking.print',
            'customer.view', 'customer.create', 'checkin.manage', 'payment.create',
            'open_play.view', 'open_play.checkin', 'open_play.manage_players',
            'products.view', 'products.sell',
            'calendar.view', 'calendar.manage',
            'court.availability.view'
        ],
        'court_staff' => [
            'booking.view', 'checkin.manage', 'court.availability.view',
            'open_play.view', 'open_play.checkin', 'products.view', 'calendar.view'
        ],
        'customer' => [
            'facility.view', 'court.view', 'booking.create', 'booking.view_own',
            'booking.cancel_own', 'payment.create', 'payment.view_own', 'review.create',
            'open_play.view', 'products.view',
            'review.view', 'favorite.manage', 'profile.manage'
        ]
    ];
}
