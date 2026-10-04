<?php
namespace App\Infrastructure\Services;

use App\Core\Database\Connection;

class EmailNotificationService {

    /**
     * Send Court Booking Email Confirmation to:
     * 1. Customer (if email notifications enabled in account settings)
     * 2. Court Owner (Owner of the facility)
     * 3. System Administrator(s)
     */
    public static function sendBookingConfirmation(
        int $userId,
        string $bookingRef,
        string $facilityName,
        string $courtName,
        string $bookingDate,
        string $startTime,
        string $endTime,
        float $amount,
        string $paymentMethod
    ): bool {
        AsyncNotificationHelper::dispatch('booking_confirmation', [
            'user_id' => $userId,
            'booking_ref' => $bookingRef,
            'facility_name' => $facilityName,
            'court_name' => $courtName,
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'amount' => $amount,
            'payment_method' => $paymentMethod
        ]);
        return true;
    }

    public static function sendBookingConfirmationNow(
        int $userId,
        string $bookingRef,
        string $facilityName,
        string $courtName,
        string $bookingDate,
        string $startTime,
        string $endTime,
        float $amount,
        string $paymentMethod
    ): bool {
        $customer = self::getUserDetails($userId);
        $customerName = $customer ? trim($customer['first_name'] . ' ' . $customer['last_name']) : 'Customer';

        // 1. Send Email to Customer if enabled in account settings
        if (self::isEmailEnabled($userId) && $customer && !empty($customer['email'])) {
            $customerSubject = "Pikvero Court Reservation Confirmed — #{$bookingRef}";
            $customerHtml = self::buildBookingEmailHtml(
                $customerName,
                $bookingRef,
                $facilityName,
                $courtName,
                $bookingDate,
                $startTime,
                $endTime,
                $amount,
                $paymentMethod
            );
            self::sendMail($customer['email'], $customerSubject, $customerHtml);
        }

        // 2. Send Email to Court Owner
        $owner = self::getOwnerEmailByBookingRef($bookingRef);
        if ($owner && !empty($owner['email'])) {
            $ownerName = trim(($owner['first_name'] ?? '') . ' ' . ($owner['last_name'] ?? ''));
            if (empty($ownerName)) $ownerName = 'Court Owner';
            $ownerSubject = "[Owner Notice] New Court Booking — #{$bookingRef}";
            $ownerHtml = self::buildOwnerBookingEmailHtml(
                $ownerName,
                $customerName,
                $bookingRef,
                $facilityName,
                $courtName,
                $bookingDate,
                $startTime,
                $endTime,
                $amount,
                $paymentMethod
            );
            self::sendMail($owner['email'], $ownerSubject, $ownerHtml);
        }

        // 3. Send Email to System Admin(s)
        $adminEmails = self::getAdminEmails();
        foreach ($adminEmails as $adminEmail) {
            $adminSubject = "[Admin Notice] New Court Reservation — #{$bookingRef}";
            $adminHtml = self::buildAdminBookingEmailHtml(
                $customerName,
                $bookingRef,
                $facilityName,
                $courtName,
                $bookingDate,
                $startTime,
                $endTime,
                $amount,
                $paymentMethod
            );
            self::sendMail($adminEmail, $adminSubject, $adminHtml);
        }

        return true;
    }

    /**
     * Send Open Play Pass Registration Email Confirmation to:
     * 1. Customer (if email notifications enabled in account settings)
     * 2. Court Owner (Owner of the facility hosting the Open Play)
     * 3. System Administrator(s)
     */
    public static function sendOpenPlayConfirmation(
        int $userId,
        int $registrationId,
        string $sessionTitle,
        string $facilityName,
        string $sessionDate,
        string $startTime,
        string $endTime,
        float $amount,
        string $paymentMethod
    ): bool {
        AsyncNotificationHelper::dispatch('open_play_confirmation', [
            'user_id' => $userId,
            'registration_id' => $registrationId,
            'session_title' => $sessionTitle,
            'facility_name' => $facilityName,
            'session_date' => $sessionDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'amount' => $amount,
            'payment_method' => $paymentMethod
        ]);
        return true;
    }

    public static function sendOpenPlayConfirmationNow(
        int $userId,
        int $registrationId,
        string $sessionTitle,
        string $facilityName,
        string $sessionDate,
        string $startTime,
        string $endTime,
        float $amount,
        string $paymentMethod
    ): bool {
        $customer = self::getUserDetails($userId);
        $customerName = $customer ? trim($customer['first_name'] . ' ' . $customer['last_name']) : 'Player';
        $passRef = "OP-" . sprintf('%05d', $registrationId);

        if (self::isEmailEnabled($userId) && $customer && !empty($customer['email'])) {
            $customerSubject = "Pikvero Open Play Pass — #{$passRef}";
            $customerHtml = self::buildOpenPlayEmailHtml(
                $customerName,
                $passRef,
                $sessionTitle,
                $facilityName,
                $sessionDate,
                $startTime,
                $endTime,
                $amount,
                $paymentMethod
            );
            self::sendMail($customer['email'], $customerSubject, $customerHtml);
        }

        $owner = self::getOwnerEmailByOpenPlayId($registrationId);
        if ($owner && !empty($owner['email'])) {
            $ownerName = trim(($owner['first_name'] ?? '') . ' ' . ($owner['last_name'] ?? ''));
            if (empty($ownerName)) $ownerName = 'Court Owner';
            $ownerSubject = "[Owner Notice] Open Play Session Entry — Pass #{$passRef}";
            $ownerHtml = self::buildOwnerOpenPlayEmailHtml(
                $ownerName,
                $customerName,
                $passRef,
                $sessionTitle,
                $facilityName,
                $sessionDate,
                $startTime,
                $endTime,
                $amount,
                $paymentMethod
            );
            self::sendMail($owner['email'], $ownerSubject, $ownerHtml);
        }

        $adminEmails = self::getAdminEmails();
        foreach ($adminEmails as $adminEmail) {
            $adminSubject = "[Admin Notice] Open Play Pass Joined — #{$passRef}";
            $adminHtml = self::buildAdminOpenPlayEmailHtml(
                $customerName,
                $passRef,
                $sessionTitle,
                $facilityName,
                $sessionDate,
                $startTime,
                $endTime,
                $amount,
                $paymentMethod
            );
            self::sendMail($adminEmail, $adminSubject, $adminHtml);
        }

        return true;
    }

    public static function sendPayoutRequestConfirmation(
        int $ownerUserId,
        int $payoutId,
        string $referenceNo,
        float $amount,
        string $accountName,
        string $accountNumber,
        string $orgName = ''
    ): bool {
        AsyncNotificationHelper::dispatch('payout_request', [
            'owner_user_id' => $ownerUserId,
            'payout_id' => $payoutId,
            'reference_no' => $referenceNo,
            'amount' => $amount,
            'gcash_name' => $accountName,
            'gcash_number' => $accountNumber,
            'org_name' => $orgName
        ]);
        return true;
    }

    public static function sendPayoutRequestConfirmationNow(
        int $ownerUserId,
        int $payoutId,
        string $referenceNo,
        float $amount,
        string $accountName,
        string $accountNumber,
        string $orgName = ''
    ): bool {
        $owner = self::getUserDetails($ownerUserId);
        $ownerName = $owner ? trim($owner['first_name'] . ' ' . $owner['last_name']) : 'Court Owner';
        $formattedAmount = number_format($amount, 2);

        if (self::isEmailEnabled($ownerUserId) && $owner && !empty($owner['email'])) {
            $ownerSubject = "Pikvero Payout Request Submitted — #{$referenceNo}";
            $ownerHtml = self::buildOwnerPayoutRequestEmailHtml(
                $ownerName,
                $referenceNo,
                $formattedAmount,
                $accountName,
                $accountNumber,
                $orgName
            );
            self::sendMail($owner['email'], $ownerSubject, $ownerHtml);
        }

        $adminEmails = self::getAdminEmails();
        foreach ($adminEmails as $adminEmail) {
            $adminSubject = "[Admin Alert] New Payout Request — #{$referenceNo}";
            $adminHtml = self::buildAdminPayoutRequestEmailHtml(
                $ownerName,
                $referenceNo,
                $formattedAmount,
                $accountName,
                $accountNumber,
                $orgName
            );
            self::sendMail($adminEmail, $adminSubject, $adminHtml);
        }

        return true;
    }

    public static function sendPayoutStatusUpdate(
        int $ownerUserId,
        int $payoutId,
        string $referenceNo,
        float $amount,
        string $status,
        string $adminNotes = '',
        string $accountName = '',
        string $accountNumber = ''
    ): bool {
        AsyncNotificationHelper::dispatch('payout_status', [
            'owner_user_id' => $ownerUserId,
            'payout_id' => $payoutId,
            'reference_no' => $referenceNo,
            'amount' => $amount,
            'status' => $status,
            'admin_notes' => $adminNotes,
            'gcash_name' => $accountName,
            'gcash_number' => $accountNumber
        ]);
        return true;
    }

    public static function sendPayoutStatusUpdateNow(
        int $ownerUserId,
        int $payoutId,
        string $referenceNo,
        float $amount,
        string $status,
        string $adminNotes = '',
        string $accountName = '',
        string $accountNumber = ''
    ): bool {
        $owner = self::getUserDetails($ownerUserId);
        $ownerName = $owner ? trim($owner['first_name'] . ' ' . $owner['last_name']) : 'Court Owner';
        $formattedAmount = number_format($amount, 2);
        $statusUpper = strtoupper($status);

        if (self::isEmailEnabled($ownerUserId) && $owner && !empty($owner['email'])) {
            $ownerSubject = "Pikvero Payout #{$referenceNo} Status Update: {$statusUpper}";
            $ownerHtml = self::buildOwnerPayoutStatusEmailHtml(
                $ownerName,
                $referenceNo,
                $formattedAmount,
                $statusUpper,
                $adminNotes,
                $accountName,
                $accountNumber
            );
            self::sendMail($owner['email'], $ownerSubject, $ownerHtml);
        }

        $adminEmails = self::getAdminEmails();
        foreach ($adminEmails as $adminEmail) {
            $adminSubject = "[Admin Notice] Payout #{$referenceNo} Updated to {$statusUpper}";
            $adminHtml = self::buildAdminPayoutStatusEmailHtml(
                $ownerName,
                $referenceNo,
                $formattedAmount,
                $statusUpper,
                $adminNotes
            );
            self::sendMail($adminEmail, $adminSubject, $adminHtml);
        }

        return true;
    }

    public static function sendBookingCancellation(
        int $customerId,
        string $bookingRef,
        string $facilityName,
        string $courtName,
        string $bookingDate,
        string $startTime,
        string $endTime,
        float $amount,
        string $reason = ''
    ): bool {
        AsyncNotificationHelper::dispatch('booking_cancellation', [
            'user_id' => $customerId,
            'booking_ref' => $bookingRef,
            'facility_name' => $facilityName,
            'court_name' => $courtName,
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'amount' => $amount,
            'reason' => $reason
        ]);
        return true;
    }

    public static function sendBookingCancellationNow(
        int $customerId,
        string $bookingRef,
        string $facilityName,
        string $courtName,
        string $bookingDate,
        string $startTime,
        string $endTime,
        float $amount,
        string $reason = ''
    ): bool {
        $customer = self::getUserDetails($customerId);
        $customerName = $customer ? trim($customer['first_name'] . ' ' . $customer['last_name']) : 'Player';

        if (self::isEmailEnabled($customerId) && $customer && !empty($customer['email'])) {
            $subject = "Pikvero Court Reservation Canceled — #{$bookingRef}";
            $html = self::buildCustomerBookingCancellationEmailHtml(
                $customerName,
                $bookingRef,
                $facilityName,
                $courtName,
                $bookingDate,
                $startTime,
                $endTime,
                $amount,
                $reason
            );
            self::sendMail($customer['email'], $subject, $html);
        }

        $owner = self::getOwnerByBookingRef($bookingRef);
        if ($owner && !empty($owner['email'])) {
            $ownerName = trim(($owner['first_name'] ?? '') . ' ' . ($owner['last_name'] ?? ''));
            if (empty($ownerName)) $ownerName = 'Facility Manager';

            $ownerSubject = "[Owner Notice] Reservation Canceled — #{$bookingRef}";
            $ownerHtml = self::buildOwnerBookingCancellationEmailHtml(
                $ownerName,
                $customerName,
                $bookingRef,
                $facilityName,
                $courtName,
                $bookingDate,
                $startTime,
                $endTime,
                $amount,
                $reason
            );
            self::sendMail($owner['email'], $ownerSubject, $ownerHtml);
        }

        $adminEmails = self::getAdminEmails();
        foreach ($adminEmails as $adminEmail) {
            $adminSubject = "[Admin Notice] Reservation Canceled — #{$bookingRef}";
            $adminHtml = self::buildAdminBookingCancellationEmailHtml(
                $customerName,
                $bookingRef,
                $facilityName,
                $courtName,
                $bookingDate,
                $startTime,
                $endTime,
                $amount,
                $reason
            );
            self::sendMail($adminEmail, $adminSubject, $adminHtml);
        }

        return true;
    }

    public static function sendRefundConfirmation(
        int $customerId,
        string $bookingRef,
        string $facilityName,
        string $courtName,
        string $bookingDate,
        string $startTime,
        string $endTime,
        float $refundAmount,
        string $reason = ''
    ): bool {
        AsyncNotificationHelper::dispatch('booking_refund', [
            'customer_id' => $customerId,
            'booking_ref' => $bookingRef,
            'facility_name' => $facilityName,
            'court_name' => $courtName,
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'refund_amount' => $refundAmount,
            'reason' => $reason
        ]);
        return true;
    }

    public static function sendRefundConfirmationNow(
        int $customerId,
        string $bookingRef,
        string $facilityName,
        string $courtName,
        string $bookingDate,
        string $startTime,
        string $endTime,
        float $refundAmount,
        string $reason = ''
    ): bool {
        $customer = self::getUserDetails($customerId);
        $customerName = $customer ? trim($customer['first_name'] . ' ' . $customer['last_name']) : 'Player';

        if (self::isEmailEnabled($customerId) && $customer && !empty($customer['email'])) {
            $subject = "Pikvero Refund Confirmation — #{$bookingRef}";
            $html = self::buildCustomerRefundEmailHtml(
                $customerName,
                $bookingRef,
                $facilityName,
                $courtName,
                $bookingDate,
                $startTime,
                $endTime,
                $refundAmount,
                $reason
            );
            self::sendMail($customer['email'], $subject, $html);
        }

        $owner = self::getOwnerByBookingRef($bookingRef);
        if ($owner && !empty($owner['email'])) {
            $ownerName = trim(($owner['first_name'] ?? '') . ' ' . ($owner['last_name'] ?? ''));
            if (empty($ownerName)) $ownerName = 'Facility Manager';

            $ownerSubject = "[Owner Notice] Refund Processed — #{$bookingRef}";
            $ownerHtml = self::buildOwnerRefundEmailHtml(
                $ownerName,
                $customerName,
                $bookingRef,
                $facilityName,
                $courtName,
                $refundAmount,
                $reason
            );
            self::sendMail($owner['email'], $ownerSubject, $ownerHtml);
        }

        $adminEmails = self::getAdminEmails();
        foreach ($adminEmails as $adminEmail) {
            $adminSubject = "[Admin Notice] Refund Processed — #{$bookingRef}";
            $adminHtml = self::buildAdminRefundEmailHtml(
                $customerName,
                $bookingRef,
                $facilityName,
                $courtName,
                $refundAmount,
                $reason
            );
            self::sendMail($adminEmail, $adminSubject, $adminHtml);
        }

        return true;
    }

    public static function sendPaymentAddedReceipt(
        int $customerId,
        string $bookingRef,
        string $facilityName,
        string $courtName,
        string $bookingDate,
        string $startTime,
        string $endTime,
        float $amount,
        string $paymentMethod = 'Cash'
    ): bool {
        AsyncNotificationHelper::dispatch('payment_added', [
            'customer_id' => $customerId,
            'booking_ref' => $bookingRef,
            'facility_name' => $facilityName,
            'court_name' => $courtName,
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'amount' => $amount,
            'payment_method' => $paymentMethod
        ]);
        return true;
    }

    public static function sendPaymentAddedReceiptNow(
        int $customerId,
        string $bookingRef,
        string $facilityName,
        string $courtName,
        string $bookingDate,
        string $startTime,
        string $endTime,
        float $amount,
        string $paymentMethod = 'Cash'
    ): bool {
        $customer = self::getUserDetails($customerId);
        $customerName = $customer ? trim($customer['first_name'] . ' ' . $customer['last_name']) : 'Player';

        // 1. Send Email to Customer if enabled
        if (self::isEmailEnabled($customerId) && $customer && !empty($customer['email'])) {
            $subject = "Pikvero Official Receipt — Payment Received #{$bookingRef}";
            $html = self::buildCustomerPaymentAddedEmailHtml(
                $customerName,
                $bookingRef,
                $facilityName,
                $courtName,
                $bookingDate,
                $startTime,
                $endTime,
                $amount,
                $paymentMethod
            );
            self::sendMail($customer['email'], $subject, $html);
        }

        // 2. Send Email to Court Owner
        $owner = self::getOwnerByBookingRef($bookingRef);
        if ($owner && !empty($owner['email'])) {
            $ownerName = trim(($owner['first_name'] ?? '') . ' ' . ($owner['last_name'] ?? ''));
            if (empty($ownerName)) $ownerName = 'Facility Manager';

            $ownerSubject = "[Owner Notice] Payment Recorded — #{$bookingRef}";
            $ownerHtml = self::buildOwnerPaymentAddedEmailHtml(
                $ownerName,
                $customerName,
                $bookingRef,
                $facilityName,
                $courtName,
                $amount,
                $paymentMethod
            );
            self::sendMail($owner['email'], $ownerSubject, $ownerHtml);
        }

        // 3. Send Email to System Admins
        $adminEmails = self::getAdminEmails();
        foreach ($adminEmails as $adminEmail) {
            $adminSubject = "[Admin Notice] Payment Recorded — #{$bookingRef}";
            $adminHtml = self::buildAdminPaymentAddedEmailHtml(
                $customerName,
                $bookingRef,
                $facilityName,
                $courtName,
                $amount,
                $paymentMethod
            );
            self::sendMail($adminEmail, $adminSubject, $adminHtml);
        }

        return true;
    }

    /**
     * Check if user enabled email notifications in account settings
     */
    public static function isEmailEnabled(int $userId): bool {
        if ($userId <= 0) return false;
        $db = Connection::getInstance();
        $pref = $db->selectOne(
            "SELECT email_notifications FROM notification_preferences WHERE user_id = ? LIMIT 1",
            [$userId],
            'i'
        );
        return $pref ? ((int)($pref['email_notifications'] ?? 1) === 1) : true;
    }

    /**
     * Fetch user details
     */
    private static function getUserDetails(int $userId): ?array {
        $db = Connection::getInstance();
        return $db->selectOne(
            "SELECT email, first_name, last_name FROM users WHERE id = ? LIMIT 1",
            [$userId],
            'i'
        );
    }

    /**
     * Get Court Owner Email by Booking Reference
     */
    private static function getOwnerEmailByBookingRef(string $bookingRef): ?array {
        try {
            $db = Connection::getInstance();
            $row = $db->selectOne("
                SELECT u.id, u.email, u.first_name, u.last_name, o.name AS org_name
                FROM bookings b
                JOIN facilities f ON b.facility_id = f.id
                JOIN organizations o ON f.organization_id = o.id
                JOIN users u ON o.owner_id = u.id
                WHERE b.booking_reference = ?
                LIMIT 1
            ", [$bookingRef], 's');

            if (!$row || empty($row['email'])) {
                $row = $db->selectOne("
                    SELECT u.id, u.email, u.first_name, u.last_name, o.name AS org_name
                    FROM organizations o
                    JOIN users u ON o.owner_id = u.id
                    WHERE u.email IS NOT NULL AND u.email != ''
                    ORDER BY o.id ASC
                    LIMIT 1
                ");
            }

            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get Court Owner Email by Open Play Registration ID
     */
    private static function getOwnerEmailByOpenPlayId(int $regId): ?array {
        try {
            $db = Connection::getInstance();
            $row = $db->selectOne("
                SELECT u.id, u.email, u.first_name, u.last_name, o.name AS org_name
                FROM open_play_registrations r
                JOIN open_play_sessions s ON r.session_id = s.id
                JOIN facilities f ON s.facility_id = f.id
                JOIN organizations o ON f.organization_id = o.id
                JOIN users u ON o.owner_id = u.id
                WHERE r.id = ?
                LIMIT 1
            ", [$regId], 'i');

            if (!$row || empty($row['email'])) {
                $row = $db->selectOne("
                    SELECT u.id, u.email, u.first_name, u.last_name, o.name AS org_name
                    FROM organizations o
                    JOIN users u ON o.owner_id = u.id
                    WHERE u.email IS NOT NULL AND u.email != ''
                    ORDER BY o.id ASC
                    LIMIT 1
                ");
            }

            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get Platform Admin Email Addresses
     */
    private static function getAdminEmails(): array {
        try {
            $db = Connection::getInstance();
            $rows = $db->select("
                SELECT DISTINCT u.email 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE (LOWER(r.name) IN ('admin', 'super_admin', 'superadmin', 'administrator', 'platform_admin') OR u.role_id IN (1, 2))
                  AND u.email IS NOT NULL AND u.email != ''
            ");

            $emails = [];
            if (!empty($rows)) {
                foreach ($rows as $r) {
                    if (!empty($r['email'])) {
                        $emails[] = trim($r['email']);
                    }
                }
            }

            if (!in_array('alvin100golosino@gmail.com', $emails)) {
                $emails[] = 'alvin100golosino@gmail.com';
            }

            return array_unique($emails);
        } catch (\Throwable $e) {
            return ['alvin100golosino@gmail.com'];
        }
    }

    private static array $mailQueue = [];
    private static bool $shutdownRegistered = false;

    /**
     * Queue HTML mail for background shutdown dispatch so HTTP response returns instantly to user
     */
    private static function sendMail(string $to, string $subject, string $htmlBody): bool {
        if (empty($to)) return false;

        self::$mailQueue[] = [
            'to' => $to,
            'subject' => $subject,
            'html' => $htmlBody
        ];

        if (!self::$shutdownRegistered) {
            self::$shutdownRegistered = true;
            register_shutdown_function(function() {
                if (function_exists('fastcgi_finish_request')) {
                    @fastcgi_finish_request();
                } else {
                    @ob_flush();
                    @flush();
                }
                foreach (self::$mailQueue as $item) {
                    self::sendMailNow($item['to'], $item['subject'], $item['html']);
                }
            });
        }

        return true;
    }

    /**
     * Send HTML mail via PHPMailer SMTP with PHP mail() fallback and file logging
     */
    public static function sendMailNow(string $to, string $subject, string $htmlBody): bool {
        if (empty($to)) return false;

        $logFile = __DIR__ . '/../../../storage/logs/sent_emails.log';
        $logDir  = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $sent = false;
        $error = '';

        // Load config if available
        $configFile = __DIR__ . '/../../../config/mail.php';
        $config = file_exists($configFile) ? include $configFile : [];

        $smtpHost  = $config['host'] ?? 'smtp.gmail.com';
        $smtpPort  = $config['port'] ?? 587;
        $smtpUser  = $config['username'] ?? 'alvin100golosino@gmail.com';
        $smtpPass  = str_replace(' ', '', $config['password'] ?? '');
        $fromEmail = $config['from_address'] ?? 'no-reply@pikvero.com';
        $fromName  = $config['from_name'] ?? 'Pikvero Courts';

        $phpmailerPath = __DIR__ . '/../../../phpmailer/vendor/autoload.php';
        if (file_exists($phpmailerPath) && !empty($smtpUser) && !empty($smtpPass)) {
            try {
                require_once $phpmailerPath;
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $smtpHost;
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtpUser;
                $mail->Password   = $smtpPass;
                $mail->SMTPSecure = 'tls';
                $mail->Port       = $smtpPort;
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];

                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress(trim($to));
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $htmlBody;

                $mail->send();
                $sent = true;
            } catch (\Throwable $e) {
                $error = 'SMTP Error: ' . $e->getMessage();
            }
        }

        if (!$sent) {
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
            $headers .= "Reply-To: support@pikvero.com\r\n";

            try {
                $sent = @mail($to, $subject, $htmlBody, $headers);
                if (!$sent && empty($error)) {
                    $error = 'Local mail() unconfigured (logged to sent_emails.log)';
                }
            } catch (\Throwable $e) {
                $error = 'mail() exception: ' . $e->getMessage();
            }
        }

        // Always record email in sent_emails.log
        $logEntry = sprintf(
            "[%s] TO: %s | SUBJECT: %s | DISPATCH: %s%s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $sent ? 'SENT_VIA_SMTP' : 'LOGGED_TO_STORAGE',
            $error ? " | NOTE: {$error}" : ""
        );
        @file_put_contents($logFile, $logEntry, FILE_APPEND);

        return true;
    }

    /**
     * Format time string into 12-Hour format (e.g., 6:00 PM)
     */
    private static function formatTime12h(string $timeStr): string {
        if (empty($timeStr)) return '';
        $ts = strtotime($timeStr);
        return $ts ? date('g:i A', $ts) : $timeStr;
    }

    /**
     * Get Facility Address by Name from Database
     */
    private static function getFacilityAddress(string $facilityName): string {
        try {
            $db = Connection::getInstance();
            $f = $db->selectOne("SELECT address, city FROM facilities WHERE name LIKE ? LIMIT 1", ["%{$facilityName}%"], 's');
            if ($f && !empty($f['address'])) {
                return trim($f['address'] . (!empty($f['city']) ? ', ' . $f['city'] : ''));
            }
        } catch (\Throwable $e) {}
        return 'CPG Avenue, Dampas District, Tagbilaran City, Bohol';
    }

    /**
     * Customer Booking Email Template
     */
    private static function buildBookingEmailHtml(
        string $name,
        string $ref,
        string $facility,
        string $court,
        string $date,
        string $startTime,
        string $endTime,
        float $amount,
        string $pm
    ): string {
        $formattedAmount = number_format($amount, 2);
        $timeWindow = self::formatTime12h($startTime) . ' – ' . self::formatTime12h($endTime);
        $facilityAddress = self::getFacilityAddress($facility);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #eafc8d; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #eafc8d; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #f8faf9; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>🎾 Pikvero Court Reserved!</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">Hi <strong>{$name}</strong>,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">Your court reservation is confirmed! Here are your official booking details:</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">BOOKING REF: {$ref}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Facility &amp; Court</td><td class="row-val">{$facility} — <span style="color: #2563eb;">{$court}</span></td></tr>
          <tr><td class="row-label">Facility Address</td><td class="row-val" style="color:#3b4e48;">{$facilityAddress}</td></tr>
          <tr><td class="row-label">Reservation Date</td><td class="row-val">{$date}</td></tr>
          <tr><td class="row-label">Time Window</td><td class="row-val" style="color: #15803d;">{$timeWindow}</td></tr>
          <tr><td class="row-label">Payment Method</td><td class="row-val">{$pm}</td></tr>
          <tr><td class="row-label">Total Amount Paid</td><td class="row-val" style="font-size: 16px; color: #ff5e3a;">₱{$formattedAmount}</td></tr>
        </table>
      </div>

      <p style="font-size: 13px; color: #555;">Present this email or quote reference code <strong>{$ref}</strong> upon arrival.</p>
    </div>
    <div class="footer">
      Pikvero Court Management System &bull; See you on the court!
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Court Owner Booking Notification Template
     */
    private static function buildOwnerBookingEmailHtml(
        string $ownerName,
        string $customerName,
        string $ref,
        string $facility,
        string $court,
        string $date,
        string $startTime,
        string $endTime,
        float $amount,
        string $pm
    ): string {
        $formattedAmount = number_format($amount, 2);
        $timeWindow = self::formatTime12h($startTime) . ' – ' . self::formatTime12h($endTime);
        $facilityAddress = self::getFacilityAddress($facility);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #ffbe0b; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #ffbe0b; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #fffdf5; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>📢 New Court Reservation Received!</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">Hi <strong>{$ownerName}</strong>,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">A new court reservation has been booked for your facility <strong>{$facility}</strong>.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">BOOKING REF: {$ref}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Booked By Customer</td><td class="row-val" style="color:#0d211d;">{$customerName}</td></tr>
          <tr><td class="row-label">Facility &amp; Court</td><td class="row-val">{$facility} — <span style="color:#2563eb;">{$court}</span></td></tr>
          <tr><td class="row-label">Facility Address</td><td class="row-val" style="color:#3b4e48;">{$facilityAddress}</td></tr>
          <tr><td class="row-label">Reservation Date</td><td class="row-val">{$date}</td></tr>
          <tr><td class="row-label">Time Window</td><td class="row-val" style="color:#15803d;">{$timeWindow}</td></tr>
          <tr><td class="row-label">Payment Method</td><td class="row-val">{$pm}</td></tr>
          <tr><td class="row-label">Booking Amount</td><td class="row-val" style="font-size: 16px; color:#059669;">₱{$formattedAmount}</td></tr>
        </table>
      </div>

      <p style="font-size: 13px; color: #555;">This schedule is reserved in your facility portal dashboard.</p>
    </div>
    <div class="footer">
      Pikvero Owner Portal &bull; Court Manager Notification
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Admin Booking Notification Template
     */
    private static function buildAdminBookingEmailHtml(
        string $customerName,
        string $ref,
        string $facility,
        string $court,
        string $date,
        string $startTime,
        string $endTime,
        float $amount,
        string $pm
    ): string {
        $formattedAmount = number_format($amount, 2);
        $timeWindow = self::formatTime12h($startTime) . ' – ' . self::formatTime12h($endTime);
        $facilityAddress = self::getFacilityAddress($facility);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #a7f3d0; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #a7f3d0; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #f0fdf4; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>⚙️ Admin System Alert: New Booking</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">System Administrator,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">A new court reservation has been processed in the system.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">BOOKING REF: {$ref}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Booked By Customer</td><td class="row-val">{$customerName}</td></tr>
          <tr><td class="row-label">Facility &amp; Court</td><td class="row-val">{$facility} — {$court}</td></tr>
          <tr><td class="row-label">Facility Address</td><td class="row-val" style="color:#3b4e48;">{$facilityAddress}</td></tr>
          <tr><td class="row-label">Reservation Date</td><td class="row-val">{$date}</td></tr>
          <tr><td class="row-label">Time Window</td><td class="row-val" style="color:#15803d;">{$timeWindow}</td></tr>
          <tr><td class="row-label">Payment Method</td><td class="row-val">{$pm}</td></tr>
          <tr><td class="row-label">Booking Amount</td><td class="row-val" style="font-size: 16px; color:#15803d;">₱{$formattedAmount}</td></tr>
        </table>
      </div>
    </div>
    <div class="footer">
      Pikvero System Administration &bull; Automated Platform Alert
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Customer Open Play Email Template
     */
    private static function buildOpenPlayEmailHtml(
        string $name,
        string $passRef,
        string $sessionTitle,
        string $facility,
        string $date,
        string $startTime,
        string $endTime,
        float $amount,
        string $pm
    ): string {
        $formattedAmount = number_format($amount, 2);
        $timeWindow = self::formatTime12h($startTime) . ' – ' . self::formatTime12h($endTime);
        $facilityAddress = self::getFacilityAddress($facility);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #7dd3fc; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #eafc8d; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #f8faf9; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>🔥 Open Play Entry Pass</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">Hi <strong>{$name}</strong>,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">You're officially registered for Open Play social pickleball! Here is your entry pass:</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">PASS REF: {$passRef}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Session Title</td><td class="row-val">{$sessionTitle}</td></tr>
          <tr><td class="row-label">Facility</td><td class="row-val">{$facility}</td></tr>
          <tr><td class="row-label">Facility Address</td><td class="row-val" style="color:#3b4e48;">{$facilityAddress}</td></tr>
          <tr><td class="row-label">Session Date</td><td class="row-val">{$date}</td></tr>
          <tr><td class="row-label">Time Window</td><td class="row-val" style="color: #15803d;">{$timeWindow}</td></tr>
          <tr><td class="row-label">Payment Method</td><td class="row-val">{$pm}</td></tr>
          <tr><td class="row-label">Entry Fee Paid</td><td class="row-val" style="font-size: 16px; color: #ff5e3a;">₱{$formattedAmount}</td></tr>
        </table>
      </div>

      <p style="font-size: 13px; color: #555;">Present pass reference <strong>{$passRef}</strong> upon check-in.</p>
    </div>
    <div class="footer">
      Pikvero Social Open Play &bull; Happy Playing!
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Court Owner Open Play Notification Template
     */
    private static function buildOwnerOpenPlayEmailHtml(
        string $ownerName,
        string $customerName,
        string $passRef,
        string $sessionTitle,
        string $facility,
        string $date,
        string $startTime,
        string $endTime,
        float $amount,
        string $pm
    ): string {
        $formattedAmount = number_format($amount, 2);
        $timeWindow = self::formatTime12h($startTime) . ' – ' . self::formatTime12h($endTime);
        $facilityAddress = self::getFacilityAddress($facility);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #bae6fd; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #7dd3fc; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #f0f9ff; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>🎾 New Open Play Player Joined!</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">Hi <strong>{$ownerName}</strong>,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">A new player registered for Open Play session <strong>'{$sessionTitle}'</strong> at <strong>{$facility}</strong>.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">PASS REF: {$passRef}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Player Name</td><td class="row-val">{$customerName}</td></tr>
          <tr><td class="row-label">Session Title</td><td class="row-val">{$sessionTitle}</td></tr>
          <tr><td class="row-label">Facility &amp; Address</td><td class="row-val">{$facility} ({$facilityAddress})</td></tr>
          <tr><td class="row-label">Session Date</td><td class="row-val">{$date}</td></tr>
          <tr><td class="row-label">Time Window</td><td class="row-val" style="color:#0284c7;">{$timeWindow}</td></tr>
          <tr><td class="row-label">Payment Method</td><td class="row-val">{$pm}</td></tr>
          <tr><td class="row-label">Entry Fee</td><td class="row-val" style="font-size: 16px; color: #0284c7;">₱{$formattedAmount}</td></tr>
        </table>
      </div>
    </div>
    <div class="footer">
      Pikvero Owner Portal &bull; Open Play Manager Notification
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Admin Open Play Notification Template
     */
    private static function buildAdminOpenPlayEmailHtml(
        string $customerName,
        string $passRef,
        string $sessionTitle,
        string $facility,
        string $date,
        string $startTime,
        string $endTime,
        float $amount,
        string $pm
    ): string {
        $formattedAmount = number_format($amount, 2);
        $timeWindow = self::formatTime12h($startTime) . ' – ' . self::formatTime12h($endTime);
        $facilityAddress = self::getFacilityAddress($facility);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #cbd5e1; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #ffffff; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #f8fafc; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>⚙️ Admin System Alert: Open Play Pass</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">System Administrator,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">A new Open Play pass registration was completed.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">PASS REF: {$passRef}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Player Name</td><td class="row-val">{$customerName}</td></tr>
          <tr><td class="row-label">Session</td><td class="row-val">{$sessionTitle} at {$facility}</td></tr>
          <tr><td class="row-label">Facility Address</td><td class="row-val" style="color:#3b4e48;">{$facilityAddress}</td></tr>
          <tr><td class="row-label">Date &amp; Time</td><td class="row-val">{$date} ({$timeWindow})</td></tr>
          <tr><td class="row-label">Payment Method</td><td class="row-val">{$pm}</td></tr>
          <tr><td class="row-label">Fee Paid</td><td class="row-val" style="font-size: 16px; color: #0f766e;">₱{$formattedAmount}</td></tr>
        </table>
      </div>
    </div>
    <div class="footer">
      Pikvero System Administration &bull; Automated Platform Alert
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Owner Payout Request Submitted HTML Template
     */
    private static function buildOwnerPayoutRequestEmailHtml(
        string $ownerName,
        string $ref,
        string $formattedAmount,
        string $accName,
        string $accNum,
        string $orgName
    ): string {
        $dateStr = date('Y-m-d g:i A');
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #ffbe0b; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #ffbe0b; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #fffdf5; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>💸 GCash Payout Request Submitted</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">Hi <strong>{$ownerName}</strong>,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">Your GCash payout withdrawal request has been received and is currently pending administrator review.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">PAYOUT REF: {$ref}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Requested Amount</td><td class="row-val" style="font-size: 18px; color: #059669;">₱{$formattedAmount}</td></tr>
          <tr><td class="row-label">GCash Account Name</td><td class="row-val">{$accName}</td></tr>
          <tr><td class="row-label">GCash Account Number</td><td class="row-val" style="color: #2563eb;">{$accNum}</td></tr>
          <tr><td class="row-label">Request Timestamp</td><td class="row-val">{$dateStr}</td></tr>
          <tr><td class="row-label">Current Status</td><td class="row-val"><span style="background: #fef3c7; color: #b45309; border: 1px solid #0d211d; padding: 2px 8px; border-radius: 4px; font-size: 11px;">PENDING REVIEW</span></td></tr>
        </table>
      </div>

      <p style="font-size: 13px; color: #555;">You will receive another email as soon as the platform administrator approves or completes the fund transfer.</p>
    </div>
    <div class="footer">
      Pikvero Owner Portal &bull; Financial Payout System
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Admin Payout Request Alert HTML Template
     */
    private static function buildAdminPayoutRequestEmailHtml(
        string $ownerName,
        string $ref,
        string $formattedAmount,
        string $accName,
        string $accNum,
        string $orgName
    ): string {
        $dateStr = date('Y-m-d g:i A');
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #a7f3d0; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #a7f3d0; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #f0fdf4; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>📢 Admin Alert: New Payout Request</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">System Administrator,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">Court Owner <strong>{$ownerName}</strong> has submitted a new payout request requiring your review.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">REF: {$ref}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Court Owner</td><td class="row-val">{$ownerName}</td></tr>
          <tr><td class="row-label">Requested Amount</td><td class="row-val" style="font-size: 18px; color: #047857;">₱{$formattedAmount}</td></tr>
          <tr><td class="row-label">GCash Name</td><td class="row-val">{$accName}</td></tr>
          <tr><td class="row-label">GCash Mobile No</td><td class="row-val" style="color: #2563eb;">{$accNum}</td></tr>
          <tr><td class="row-label">Timestamp</td><td class="row-val">{$dateStr}</td></tr>
        </table>
      </div>

      <p style="font-size: 13px; color: #555;">Review and approve or process this transfer in the Admin Payout Directory.</p>
    </div>
    <div class="footer">
      Pikvero System Administration &bull; Financial Payout Alert
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Owner Payout Status Update HTML Template
     */
    private static function buildOwnerPayoutStatusEmailHtml(
        string $ownerName,
        string $ref,
        string $formattedAmount,
        string $statusUpper,
        string $adminNotes,
        string $accName,
        string $accNum
    ): string {
        $dateStr = date('Y-m-d g:i A');
        $headerColor = '#eafc8d';
        $badgeBg = '#0d211d';
        $badgeText = '#eafc8d';

        if ($statusUpper === 'REJECTED') {
            $headerColor = '#f87171';
            $badgeBg = '#7f1d1d';
            $badgeText = '#ffffff';
        } else if ($statusUpper === 'COMPLETED' || $statusUpper === 'PAID') {
            $headerColor = '#86efac';
            $badgeBg = '#065f46';
            $badgeText = '#ffffff';
        }

        $notesBlock = !empty($adminNotes) ? "<div style='margin-top:12px; padding:10px; background:#fff; border:1px dashed #0d211d; border-radius:8px;'><strong>Administrator Note:</strong> " . htmlspecialchars($adminNotes) . "</div>" : "";

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: {$headerColor}; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: {$badgeBg}; color: {$badgeText}; font-family: monospace; font-weight: 800; padding: 4px 12px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #f8faf9; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>💸 Payout Status Update: {$statusUpper}</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">Hi <strong>{$ownerName}</strong>,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">The status of your GCash payout request <strong>#{$ref}</strong> has been updated to <strong>{$statusUpper}</strong>.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">STATUS: {$statusUpper}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Payout Reference</td><td class="row-val">{$ref}</td></tr>
          <tr><td class="row-label">Payout Amount</td><td class="row-val" style="font-size: 18px; color: #0d211d;">₱{$formattedAmount}</td></tr>
          <tr><td class="row-label">GCash Account</td><td class="row-val">{$accName} ({$accNum})</td></tr>
          <tr><td class="row-label">Update Timestamp</td><td class="row-val">{$dateStr}</td></tr>
        </table>
        {$notesBlock}
      </div>
    </div>
    <div class="footer">
      Pikvero Owner Portal &bull; Payout Status Notification
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Admin Payout Status Log HTML Template
     */
    private static function buildAdminPayoutStatusEmailHtml(
        string $ownerName,
        string $ref,
        string $formattedAmount,
        string $statusUpper,
        string $adminNotes
    ): string {
        $dateStr = date('Y-m-d g:i A');
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #cbd5e1; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #ffffff; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #f8fafc; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>⚙️ Admin Log: Payout Updated</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">System Administrator,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">Payout request <strong>#{$ref}</strong> status was updated to <strong>{$statusUpper}</strong>.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">REF: {$ref}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Court Owner</td><td class="row-val">{$ownerName}</td></tr>
          <tr><td class="row-label">Amount</td><td class="row-val">₱{$formattedAmount}</td></tr>
          <tr><td class="row-label">New Status</td><td class="row-val">{$statusUpper}</td></tr>
          <tr><td class="row-label">Timestamp</td><td class="row-val">{$dateStr}</td></tr>
        </table>
      </div>
    </div>
    <div class="footer">
      Pikvero System Administration &bull; Automated Platform Alert
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Get Court Owner Details by Booking Reference
     */
    private static function getOwnerByBookingRef(string $bookingRef): ?array {
        try {
            $db = Connection::getInstance();
            return $db->selectOne("
                SELECT u.id, u.first_name, u.last_name, u.email
                FROM bookings b
                JOIN facilities f ON b.facility_id = f.id
                JOIN organizations o ON f.organization_id = o.id
                JOIN users u ON o.owner_id = u.id
                WHERE b.booking_reference = ?
                LIMIT 1
            ", [$bookingRef], 's');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Customer Booking Cancellation HTML Template
     */
    private static function buildCustomerBookingCancellationEmailHtml(
        string $name,
        string $ref,
        string $facility,
        string $court,
        string $date,
        string $startTime,
        string $endTime,
        float $amount,
        string $reason
    ): string {
        $formattedAmount = number_format($amount, 2);
        $timeWindow = self::formatTime12h($startTime) . ' – ' . self::formatTime12h($endTime);
        $facilityAddress = self::getFacilityAddress($facility);
        $reasonBlock = !empty($reason) ? "<div style='margin-top:12px; padding:10px; background:#fff; border:1px dashed #0d211d; border-radius:8px;'><strong>Reason:</strong> " . htmlspecialchars($reason) . "</div>" : "";

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #f87171; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; color: #ffffff; }
    .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #7f1d1d; color: #ffffff; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #fef2f2; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>❌ Court Reservation Canceled</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">Hi <strong>{$name}</strong>,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">Your court reservation <strong>#{$ref}</strong> has been successfully canceled.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">CANCELED REF: {$ref}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Facility &amp; Court</td><td class="row-val">{$facility} — <span style="color:#2563eb;">{$court}</span></td></tr>
          <tr><td class="row-label">Facility Address</td><td class="row-val" style="color:#3b4e48;">{$facilityAddress}</td></tr>
          <tr><td class="row-label">Reservation Date</td><td class="row-val">{$date}</td></tr>
          <tr><td class="row-label">Time Window</td><td class="row-val" style="color:#b91c1c;">{$timeWindow}</td></tr>
          <tr><td class="row-label">Original Booking Fee</td><td class="row-val">₱{$formattedAmount}</td></tr>
        </table>
        {$reasonBlock}
      </div>

      <p style="font-size: 13px; color: #555;">If you need assistance with rescheduling or refunds, please reach out to court support.</p>
    </div>
    <div class="footer">
      Pikvero Court Management System &bull; Cancellation Notice
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Court Owner Booking Cancellation HTML Template
     */
    private static function buildOwnerBookingCancellationEmailHtml(
        string $ownerName,
        string $customerName,
        string $ref,
        string $facility,
        string $court,
        string $date,
        string $startTime,
        string $endTime,
        float $amount,
        string $reason
    ): string {
        $formattedAmount = number_format($amount, 2);
        $timeWindow = self::formatTime12h($startTime) . ' – ' . self::formatTime12h($endTime);
        $facilityAddress = self::getFacilityAddress($facility);
        $reasonBlock = !empty($reason) ? "<div style='margin-top:12px; padding:10px; background:#fff; border:1px dashed #0d211d; border-radius:8px;'><strong>Reason Provided:</strong> " . htmlspecialchars($reason) . "</div>" : "";

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #fdba74; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #fdba74; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #fff7ed; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>⚠️ Booking Canceled by Customer</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">Hi <strong>{$ownerName}</strong>,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">Customer <strong>{$customerName}</strong> has canceled their court reservation <strong>#{$ref}</strong> at <strong>{$facility}</strong>.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">REF: {$ref}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Player Name</td><td class="row-val">{$customerName}</td></tr>
          <tr><td class="row-label">Facility &amp; Court</td><td class="row-val">{$facility} — <span style="color:#2563eb;">{$court}</span></td></tr>
          <tr><td class="row-label">Facility Address</td><td class="row-val" style="color:#3b4e48;">{$facilityAddress}</td></tr>
          <tr><td class="row-label">Reservation Date</td><td class="row-val">{$date}</td></tr>
          <tr><td class="row-label">Time Window</td><td class="row-val" style="color:#c2410c;">{$timeWindow}</td></tr>
          <tr><td class="row-label">Booking Fee</td><td class="row-val">₱{$formattedAmount}</td></tr>
        </table>
        {$reasonBlock}
      </div>

      <p style="font-size: 13px; color: #555;">This court time slot has been reopened and made available in your schedule.</p>
    </div>
    <div class="footer">
      Pikvero Owner Portal &bull; Court Schedule Alert
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Admin Booking Cancellation HTML Template
     */
    private static function buildAdminBookingCancellationEmailHtml(
        string $customerName,
        string $ref,
        string $facility,
        string $court,
        string $date,
        string $startTime,
        string $endTime,
        float $amount,
        string $reason
    ): string {
        $formattedAmount = number_format($amount, 2);
        $timeWindow = self::formatTime12h($startTime) . ' – ' . self::formatTime12h($endTime);
        $facilityAddress = self::getFacilityAddress($facility);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #cbd5e1; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #ffffff; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #f8fafc; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>⚙️ Admin Log: Reservation Canceled</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">System Administrator,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">Reservation <strong>#{$ref}</strong> was canceled by customer <strong>{$customerName}</strong>.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">REF: {$ref}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Customer</td><td class="row-val">{$customerName}</td></tr>
          <tr><td class="row-label">Facility &amp; Court</td><td class="row-val">{$facility} — {$court}</td></tr>
          <tr><td class="row-label">Date &amp; Time</td><td class="row-val">{$date} ({$timeWindow})</td></tr>
          <tr><td class="row-label">Amount</td><td class="row-val">₱{$formattedAmount}</td></tr>
        </table>
      </div>
    </div>
    <div class="footer">
      Pikvero System Administration &bull; Automated Platform Alert
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Customer Refund HTML Template
     */
    private static function buildCustomerRefundEmailHtml(
        string $name,
        string $ref,
        string $facility,
        string $court,
        string $date,
        string $startTime,
        string $endTime,
        float $refundAmount,
        string $reason
    ): string {
        $formattedAmount = number_format($refundAmount, 2);
        $timeWindow = self::formatTime12h($startTime) . ' – ' . self::formatTime12h($endTime);
        $facilityAddress = self::getFacilityAddress($facility);
        $reasonBlock = !empty($reason) ? "<div style='margin-top:12px; padding:10px; background:#fff; border:1px dashed #0d211d; border-radius:8px;'><strong>Refund Reason / Note:</strong> " . htmlspecialchars($reason) . "</div>" : "";

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #38bdf8; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #38bdf8; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #f0f9ff; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>💸 Refund Processed Confirmation</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">Hi <strong>{$name}</strong>,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">A refund has been processed for your court reservation <strong>#{$ref}</strong>.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">REFUND REF: {$ref}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Refund Amount Issued</td><td class="row-val" style="font-size: 18px; color: #0284c7;">₱{$formattedAmount}</td></tr>
          <tr><td class="row-label">Facility &amp; Court</td><td class="row-val">{$facility} — <span style="color:#2563eb;">{$court}</span></td></tr>
          <tr><td class="row-label">Facility Address</td><td class="row-val" style="color:#3b4e48;">{$facilityAddress}</td></tr>
          <tr><td class="row-label">Reservation Date</td><td class="row-val">{$date}</td></tr>
          <tr><td class="row-label">Time Window</td><td class="row-val" style="color:#0284c7;">{$timeWindow}</td></tr>
        </table>
        {$reasonBlock}
      </div>

      <p style="font-size: 13px; color: #555;">Please allow standard processing time for the credit to reflect in your payment account.</p>
    </div>
    <div class="footer">
      Pikvero Financial Services &bull; Refund Confirmation
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Court Owner Refund HTML Template
     */
    private static function buildOwnerRefundEmailHtml(
        string $ownerName,
        string $customerName,
        string $ref,
        string $facility,
        string $court,
        float $refundAmount,
        string $reason
    ): string {
        $formattedAmount = number_format($refundAmount, 2);
        $facilityAddress = self::getFacilityAddress($facility);
        $reasonBlock = !empty($reason) ? "<div style='margin-top:12px; padding:10px; background:#fff; border:1px dashed #0d211d; border-radius:8px;'><strong>Reason:</strong> " . htmlspecialchars($reason) . "</div>" : "";

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #7dd3fc; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #7dd3fc; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #f0f9ff; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>💸 Refund Processed Alert</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">Hi <strong>{$ownerName}</strong>,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">A refund was recorded for reservation <strong>#{$ref}</strong> at <strong>{$facility}</strong>.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">REF: {$ref}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Player Name</td><td class="row-val">{$customerName}</td></tr>
          <tr><td class="row-label">Facility &amp; Address</td><td class="row-val">{$facility} ({$facilityAddress})</td></tr>
          <tr><td class="row-label">Refund Amount</td><td class="row-val" style="font-size: 18px; color: #0369a1;">₱{$formattedAmount}</td></tr>
        </table>
        {$reasonBlock}
      </div>
    </div>
    <div class="footer">
      Pikvero Owner Portal &bull; Financial Audit Notification
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Admin Refund HTML Template
     */
    private static function buildAdminRefundEmailHtml(
        string $customerName,
        string $ref,
        string $facility,
        string $court,
        float $refundAmount,
        string $reason
    ): string {
        $formattedAmount = number_format($refundAmount, 2);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #cbd5e1; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #ffffff; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #f8fafc; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>⚙️ Admin Log: Refund Processed</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">System Administrator,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">A refund of ₱{$formattedAmount} was issued for reservation <strong>#{$ref}</strong>.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">REF: {$ref}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Customer</td><td class="row-val">{$customerName}</td></tr>
          <tr><td class="row-label">Facility &amp; Court</td><td class="row-val">{$facility} — {$court}</td></tr>
          <tr><td class="row-label">Refund Amount</td><td class="row-val" style="font-size: 16px; color: #0369a1;">₱{$formattedAmount}</td></tr>
        </table>
      </div>
    </div>
    <div class="footer">
      Pikvero System Administration &bull; Automated Platform Alert
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Customer Payment Received Receipt HTML Template
     */
    private static function buildCustomerPaymentAddedEmailHtml(
        string $name,
        string $ref,
        string $facility,
        string $court,
        string $date,
        string $startTime,
        string $endTime,
        float $amount,
        string $pm
    ): string {
        $formattedAmount = number_format($amount, 2);
        $timeWindow = self::formatTime12h($startTime) . ' – ' . self::formatTime12h($endTime);
        $facilityAddress = self::getFacilityAddress($facility);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #86efac; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #86efac; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #f0fdf4; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>🧾 Official Receipt: Payment Received</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">Hi <strong>{$name}</strong>,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">Your payment for court reservation <strong>#{$ref}</strong> has been received and confirmed!</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">BOOKING REF: {$ref}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Amount Paid</td><td class="row-val" style="font-size: 18px; color: #16a34a;">₱{$formattedAmount}</td></tr>
          <tr><td class="row-label">Payment Method</td><td class="row-val">{$pm}</td></tr>
          <tr><td class="row-label">Facility &amp; Court</td><td class="row-val">{$facility} — <span style="color:#2563eb;">{$court}</span></td></tr>
          <tr><td class="row-label">Facility Address</td><td class="row-val" style="color:#3b4e48;">{$facilityAddress}</td></tr>
          <tr><td class="row-label">Reservation Date</td><td class="row-val">{$date}</td></tr>
          <tr><td class="row-label">Time Window</td><td class="row-val" style="color:#16a34a;">{$timeWindow}</td></tr>
        </table>
      </div>

      <p style="font-size: 13px; color: #555;">Thank you! Your schedule is fully confirmed.</p>
    </div>
    <div class="footer">
      Pikvero Court Management System &bull; Official Payment Receipt
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Owner Payment Received HTML Template
     */
    private static function buildOwnerPaymentAddedEmailHtml(
        string $ownerName,
        string $customerName,
        string $ref,
        string $facility,
        string $court,
        float $amount,
        string $pm
    ): string {
        $formattedAmount = number_format($amount, 2);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #a7f3d0; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #a7f3d0; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #f0fdf4; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>💰 Payment Recorded Alert</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">Hi <strong>{$ownerName}</strong>,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">Payment of ₱{$formattedAmount} was recorded for reservation <strong>#{$ref}</strong> at <strong>{$facility}</strong>.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">REF: {$ref}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Player Name</td><td class="row-val">{$customerName}</td></tr>
          <tr><td class="row-label">Amount Recorded</td><td class="row-val" style="font-size: 18px; color: #15803d;">₱{$formattedAmount}</td></tr>
          <tr><td class="row-label">Payment Method</td><td class="row-val">{$pm}</td></tr>
        </table>
      </div>
    </div>
    <div class="footer">
      Pikvero Owner Portal &bull; Payment Received Alert
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Admin Payment Received HTML Template
     */
    private static function buildAdminPaymentAddedEmailHtml(
        string $customerName,
        string $ref,
        string $facility,
        string $court,
        float $amount,
        string $pm
    ): string {
        $formattedAmount = number_format($amount, 2);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f5; margin: 0; padding: 20px; color: #0d211d; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; border: 3px solid #0d211d; border-radius: 16px; box-shadow: 6px 6px 0 #0d211d; overflow: hidden; }
    .header { background: #cbd5e1; border-bottom: 3px solid #0d211d; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; font-weight: 900; }
    .content { padding: 24px; }
    .badge { display: inline-block; background: #0d211d; color: #ffffff; font-family: monospace; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 13px; text-transform: uppercase; }
    .detail-card { background: #f8fafc; border: 2px solid #0d211d; border-radius: 12px; padding: 16px; margin: 18px 0; }
    .row-label { color: #4a5c56; font-size: 12px; font-weight: 800; font-family: monospace; text-transform: uppercase; }
    .row-val { font-weight: 800; text-align: right; }
    .footer { background: #eee9d8; border-top: 2px solid #0d211d; padding: 14px; text-align: center; font-size: 12px; color: #4a5c56; font-weight: 700; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>⚙️ Admin Log: Payment Recorded</h1>
    </div>
    <div class="content">
      <p style="font-size: 16px; margin-top: 0;">System Administrator,</p>
      <p style="font-size: 14px; color: #3b4e48; line-height: 1.5;">Payment of ₱{$formattedAmount} via {$pm} was recorded for booking <strong>#{$ref}</strong>.</p>
      
      <div style="text-align: center; margin: 12px 0;">
        <span class="badge">REF: {$ref}</span>
      </div>

      <div class="detail-card">
        <table width="100%" cellpadding="6" cellspacing="0">
          <tr><td class="row-label">Customer</td><td class="row-val">{$customerName}</td></tr>
          <tr><td class="row-label">Amount</td><td class="row-val">₱{$formattedAmount}</td></tr>
          <tr><td class="row-label">Method</td><td class="row-val">{$pm}</td></tr>
        </table>
      </div>
    </div>
    <div class="footer">
      Pikvero System Administration &bull; Automated Platform Alert
    </div>
  </div>
</body>
</html>
HTML;
    }
}
