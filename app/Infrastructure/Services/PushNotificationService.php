<?php
namespace App\Infrastructure\Services;

use App\Core\Database\Connection;

class PushNotificationService {

    /**
     * Send Court Booking Push Notifications to:
     * 1. Customer (if push_notifications is enabled in user settings)
     * 2. Court Owner (Owner of the facility)
     * 3. System Administrator(s)
     */
    public static function sendBookingConfirmation(
        int $customerId,
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
            'user_id' => $customerId,
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
        int $customerId,
        string $bookingRef,
        string $facilityName,
        string $courtName,
        string $bookingDate,
        string $startTime,
        string $endTime,
        float $amount,
        string $paymentMethod
    ): bool {
        $customer = self::getUserDetails($customerId);
        $customerName = $customer ? trim($customer['first_name'] . ' ' . $customer['last_name']) : 'Customer';
        $formattedAmount = number_format($amount, 2);
        $sTime = substr($startTime, 0, 5);
        $eTime = substr($endTime, 0, 5);

        self::sendToUser(
            $customerId,
            "Court Reservation Confirmed! 🎾",
            "Your court booking #{$bookingRef} at {$facilityName} ({$courtName}) on {$bookingDate} ({$sTime}-{$eTime}) is confirmed.",
            ['booking_reference' => $bookingRef, 'type' => 'booking_confirmation']
        );

        $owner = self::getOwnerByBookingRef($bookingRef);
        if ($owner && !empty($owner['id']) && (int)$owner['id'] !== $customerId) {
            $ownerId = (int)$owner['id'];
            self::sendToUser(
                $ownerId,
                "📢 New Booking Received! #{$bookingRef}",
                "Customer {$customerName} reserved {$facilityName} ({$courtName}) for {$bookingDate} ({$sTime}-{$eTime}). Amount: ₱{$formattedAmount}.",
                ['booking_reference' => $bookingRef, 'click_action' => '/pikvero/public/owner/reservations.php', 'type' => 'owner_booking_alert']
            );
        }

        $adminIds = self::getAdminUserIds();
        foreach ($adminIds as $adminId) {
            if ($adminId !== $customerId && (!$owner || (int)$owner['id'] !== $adminId)) {
                self::sendToUser(
                    $adminId,
                    "⚙️ New System Booking — #{$bookingRef}",
                    "Booking #{$bookingRef} placed by {$customerName} at {$facilityName} ({$courtName}) for {$bookingDate}. Amount: ₱{$formattedAmount}.",
                    ['booking_reference' => $bookingRef, 'click_action' => '/pikvero/public/admin/bookings.php', 'type' => 'admin_booking_alert']
                );
            }
        }

        return true;
    }

    public static function sendOpenPlayConfirmation(
        int $customerId,
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
            'user_id' => $customerId,
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
        int $customerId,
        int $registrationId,
        string $sessionTitle,
        string $facilityName,
        string $sessionDate,
        string $startTime,
        string $endTime,
        float $amount,
        string $paymentMethod
    ): bool {
        $customer = self::getUserDetails($customerId);
        $customerName = $customer ? trim($customer['first_name'] . ' ' . $customer['last_name']) : 'Player';
        $passRef = "OP-" . sprintf('%05d', $registrationId);
        $formattedAmount = number_format($amount, 2);
        $sTime = substr($startTime, 0, 5);
        $eTime = substr($endTime, 0, 5);

        self::sendToUser(
            $customerId,
            "Open Play Pass Issued! 🔥",
            "You're registered for '{$sessionTitle}' at {$facilityName} on {$sessionDate} ({$sTime}-{$eTime}). Pass #{$passRef}.",
            ['registration_id' => $registrationId, 'pass_ref' => $passRef, 'type' => 'open_play']
        );

        $owner = self::getOwnerByOpenPlayId($registrationId);
        if ($owner && !empty($owner['id']) && (int)$owner['id'] !== $customerId) {
            $ownerId = (int)$owner['id'];
            self::sendToUser(
                $ownerId,
                "🎾 Open Play Player Joined! Pass #{$passRef}",
                "Player {$customerName} registered for '{$sessionTitle}' at {$facilityName} on {$sessionDate}. Fee: ₱{$formattedAmount}.",
                ['registration_id' => $registrationId, 'pass_ref' => $passRef, 'click_action' => '/pikvero/public/owner/open-play.php', 'type' => 'owner_open_play_alert']
            );
        }

        $adminIds = self::getAdminUserIds();
        foreach ($adminIds as $adminId) {
            if ($adminId !== $customerId && (!$owner || (int)$owner['id'] !== $adminId)) {
                self::sendToUser(
                    $adminId,
                    "⚙️ Open Play Pass Joined — Pass #{$passRef}",
                    "Player {$customerName} registered for Open Play session '{$sessionTitle}' at {$facilityName} on {$sessionDate}.",
                    ['registration_id' => $registrationId, 'pass_ref' => $passRef, 'click_action' => '/pikvero/public/admin/open-play.php', 'type' => 'admin_open_play_alert']
                );
            }
        }

        return true;
    }

    public static function sendPayoutRequestNotification(
        int $ownerUserId,
        int $payoutId,
        string $referenceNo,
        float $amount,
        string $accountName,
        string $accountNumber
    ): bool {
        AsyncNotificationHelper::dispatch('payout_request', [
            'owner_user_id' => $ownerUserId,
            'payout_id' => $payoutId,
            'reference_no' => $referenceNo,
            'amount' => $amount,
            'gcash_name' => $accountName,
            'gcash_number' => $accountNumber
        ]);
        return true;
    }

    public static function sendPayoutRequestNotificationNow(
        int $ownerUserId,
        int $payoutId,
        string $referenceNo,
        float $amount,
        string $accountName,
        string $accountNumber
    ): bool {
        $formattedAmount = number_format($amount, 2);

        self::sendToUser(
            $ownerUserId,
            "💸 Payout Request Submitted!",
            "Your GCash payout request #{$referenceNo} for ₱{$formattedAmount} to account {$accountName} ({$accountNumber}) has been submitted and is pending review.",
            ['payout_id' => $payoutId, 'reference_no' => $referenceNo, 'click_action' => '/pikvero/public/owner/payouts.php', 'type' => 'payout_request']
        );

        $adminIds = self::getAdminUserIds();
        foreach ($adminIds as $adminId) {
            if ($adminId !== $ownerUserId) {
                self::sendToUser(
                    $adminId,
                    "📢 New Payout Request — #{$referenceNo}",
                    "A Court Owner requested a GCash payout of ₱{$formattedAmount} (Ref: #{$referenceNo}) to {$accountName} ({$accountNumber}).",
                    ['payout_id' => $payoutId, 'reference_no' => $referenceNo, 'click_action' => '/pikvero/public/admin/payouts.php', 'type' => 'admin_payout_alert']
                );
            }
        }

        return true;
    }

    public static function sendPayoutStatusUpdateNotification(
        int $ownerUserId,
        int $payoutId,
        string $referenceNo,
        float $amount,
        string $status,
        string $adminNotes = ''
    ): bool {
        AsyncNotificationHelper::dispatch('payout_status', [
            'owner_user_id' => $ownerUserId,
            'payout_id' => $payoutId,
            'reference_no' => $referenceNo,
            'amount' => $amount,
            'status' => $status,
            'admin_notes' => $adminNotes
        ]);
        return true;
    }

    public static function sendPayoutStatusUpdateNotificationNow(
        int $ownerUserId,
        int $payoutId,
        string $referenceNo,
        float $amount,
        string $status,
        string $adminNotes = ''
    ): bool {
        $formattedAmount = number_format($amount, 2);
        $statusUpper = strtoupper($status);

        $statusEmoji = '💸';
        if ($statusUpper === 'APPROVED') $statusEmoji = '✅';
        else if ($statusUpper === 'COMPLETED' || $statusUpper === 'PAID') $statusEmoji = '🎉';
        else if ($statusUpper === 'REJECTED') $statusEmoji = '❌';

        $noteText = !empty($adminNotes) ? " Note: {$adminNotes}" : "";

        self::sendToUser(
            $ownerUserId,
            "{$statusEmoji} Payout Request #{$referenceNo} {$statusUpper}",
            "Your payout request #{$referenceNo} for ₱{$formattedAmount} has been updated to {$statusUpper}.{$noteText}",
            ['payout_id' => $payoutId, 'reference_no' => $referenceNo, 'click_action' => '/pikvero/public/owner/payouts.php', 'type' => 'payout_status_update']
        );

        $adminIds = self::getAdminUserIds();
        foreach ($adminIds as $adminId) {
            if ($adminId !== $ownerUserId) {
                self::sendToUser(
                    $adminId,
                    "⚙️ Payout #{$referenceNo} Updated to {$statusUpper}",
                    "Payout #{$referenceNo} for ₱{$formattedAmount} status was updated to {$statusUpper}.{$noteText}",
                    ['payout_id' => $payoutId, 'reference_no' => $referenceNo, 'click_action' => '/pikvero/public/admin/payouts.php', 'type' => 'admin_payout_update']
                );
            }
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
        string $reason = ''
    ): bool {
        $sTime = self::formatTime12h($startTime);
        $eTime = self::formatTime12h($endTime);
        $customer = self::getUserDetails($customerId);
        $customerName = $customer ? trim($customer['first_name'] . ' ' . $customer['last_name']) : 'Player';

        self::sendToUser(
            $customerId,
            "Reservation Canceled ❌",
            "Your reservation #{$bookingRef} at {$facilityName} ({$courtName}) for {$bookingDate} ({$sTime} – {$eTime}) has been canceled.",
            ['booking_ref' => $bookingRef, 'type' => 'booking_cancellation']
        );

        $owner = self::getOwnerByBookingRef($bookingRef);
        if ($owner && !empty($owner['id']) && (int)$owner['id'] !== $customerId) {
            $ownerId = (int)$owner['id'];
            self::sendToUser(
                $ownerId,
                "⚠️ Reservation Canceled — #{$bookingRef}",
                "Customer {$customerName} canceled their reservation at {$facilityName} ({$courtName}) on {$bookingDate} ({$sTime} – {$eTime}).",
                ['booking_ref' => $bookingRef, 'click_action' => '/pikvero/public/owner/bookings.php', 'type' => 'owner_cancellation_alert']
            );
        }

        $adminIds = self::getAdminUserIds();
        foreach ($adminIds as $adminId) {
            if ($adminId !== $customerId && (!$owner || (int)$owner['id'] !== $adminId)) {
                self::sendToUser(
                    $adminId,
                    "⚙️ System Alert: Booking Canceled #{$bookingRef}",
                    "Customer {$customerName} canceled reservation #{$bookingRef} at {$facilityName} ({$courtName}) on {$bookingDate}.",
                    ['booking_ref' => $bookingRef, 'click_action' => '/pikvero/public/admin/bookings.php', 'type' => 'admin_cancellation_alert']
                );
            }
        }

        return true;
    }

    public static function sendRefundNotification(
        int $customerId,
        string $bookingRef,
        string $facilityName,
        string $courtName,
        float $refundAmount,
        string $reason = ''
    ): bool {
        AsyncNotificationHelper::dispatch('booking_refund', [
            'customer_id' => $customerId,
            'booking_ref' => $bookingRef,
            'facility_name' => $facilityName,
            'court_name' => $courtName,
            'refund_amount' => $refundAmount,
            'reason' => $reason
        ]);
        return true;
    }

    public static function sendRefundNotificationNow(
        int $customerId,
        string $bookingRef,
        string $facilityName,
        string $courtName,
        float $refundAmount,
        string $reason = ''
    ): bool {
        $formattedAmount = number_format($refundAmount, 2);
        $customer = self::getUserDetails($customerId);
        $customerName = $customer ? trim($customer['first_name'] . ' ' . $customer['last_name']) : 'Player';

        self::sendToUser(
            $customerId,
            "Refund Processed! 💸",
            "A refund of ₱{$formattedAmount} for reservation #{$bookingRef} at {$facilityName} ({$courtName}) has been processed.",
            ['booking_ref' => $bookingRef, 'type' => 'booking_refund']
        );

        $owner = self::getOwnerByBookingRef($bookingRef);
        if ($owner && !empty($owner['id']) && (int)$owner['id'] !== $customerId) {
            $ownerId = (int)$owner['id'];
            self::sendToUser(
                $ownerId,
                "💸 Refund Processed — #{$bookingRef}",
                "Refund of ₱{$formattedAmount} was processed for reservation #{$bookingRef} at {$facilityName}.",
                ['booking_ref' => $bookingRef, 'click_action' => '/pikvero/public/owner/bookings.php', 'type' => 'owner_refund_alert']
            );
        }

        $adminIds = self::getAdminUserIds();
        foreach ($adminIds as $adminId) {
            if ($adminId !== $customerId && (!$owner || (int)$owner['id'] !== $adminId)) {
                self::sendToUser(
                    $adminId,
                    "⚙️ System Alert: Refund Processed #{$bookingRef}",
                    "Refund of ₱{$formattedAmount} processed for reservation #{$bookingRef} by customer {$customerName}.",
                    ['booking_ref' => $bookingRef, 'click_action' => '/pikvero/public/admin/bookings.php', 'type' => 'admin_refund_alert']
                );
            }
        }

        return true;
    }

    public static function sendPaymentAddedNotification(
        int $customerId,
        string $bookingRef,
        string $facilityName,
        string $courtName,
        float $amount,
        string $paymentMethod = 'Cash'
    ): bool {
        AsyncNotificationHelper::dispatch('payment_added', [
            'customer_id' => $customerId,
            'booking_ref' => $bookingRef,
            'facility_name' => $facilityName,
            'court_name' => $courtName,
            'amount' => $amount,
            'payment_method' => $paymentMethod
        ]);
        return true;
    }

    public static function sendPaymentAddedNotificationNow(
        int $customerId,
        string $bookingRef,
        string $facilityName,
        string $courtName,
        float $amount,
        string $paymentMethod = 'Cash'
    ): bool {
        $formattedAmount = number_format($amount, 2);
        $customer = self::getUserDetails($customerId);
        $customerName = $customer ? trim($customer['first_name'] . ' ' . $customer['last_name']) : 'Player';

        self::sendToUser(
            $customerId,
            "Payment Received! 💳",
            "Payment of ₱{$formattedAmount} via {$paymentMethod} for reservation #{$bookingRef} at {$facilityName} was recorded.",
            ['booking_ref' => $bookingRef, 'type' => 'payment_added']
        );

        $owner = self::getOwnerByBookingRef($bookingRef);
        if ($owner && !empty($owner['id']) && (int)$owner['id'] !== $customerId) {
            $ownerId = (int)$owner['id'];
            self::sendToUser(
                $ownerId,
                "💰 Payment Recorded — #{$bookingRef}",
                "Payment of ₱{$formattedAmount} via {$paymentMethod} recorded for reservation #{$bookingRef} at {$facilityName}.",
                ['booking_ref' => $bookingRef, 'click_action' => '/pikvero/public/owner/bookings.php', 'type' => 'owner_payment_alert']
            );
        }

        $adminIds = self::getAdminUserIds();
        foreach ($adminIds as $adminId) {
            if ($adminId !== $customerId && (!$owner || (int)$owner['id'] !== $adminId)) {
                self::sendToUser(
                    $adminId,
                    "⚙️ System Alert: Payment Recorded #{$bookingRef}",
                    "Payment of ₱{$formattedAmount} recorded for reservation #{$bookingRef} by customer {$customerName}.",
                    ['booking_ref' => $bookingRef, 'click_action' => '/pikvero/public/admin/bookings.php', 'type' => 'admin_payment_alert']
                );
            }
        }

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

    private static ?string $cachedGoogleToken = null;
    private static array $fcmQueue = [];
    private static bool $shutdownRegistered = false;

    /**
     * Send a push notification to a user based on their account notification settings.
     */
    public static function sendToUser(int $userId, string $title, string $message, array $extraData = []): bool {
        if ($userId <= 0) {
            return false;
        }

        $db = Connection::getInstance();

        // 1. Check user notification settings
        $pref = $db->selectOne(
            "SELECT push_notifications, fcm_token FROM notification_preferences WHERE user_id = ? LIMIT 1",
            [$userId],
            'i'
        );

        $pushEnabled = $pref ? (int)($pref['push_notifications'] ?? 1) : 1;

        if ($pushEnabled === 0) {
            // User disabled push notifications in account settings
            return false;
        }

        // 2. Insert notification record into database for history and web popups (Instant DB write)
        $db->execute(
            "INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, 'push', 0, NOW())",
            [$userId, $title, $message],
            'iss'
        );

        // 3. Queue Firebase Cloud Messaging (FCM) Push Notification for background shutdown dispatch
        $fcmToken = $pref['fcm_token'] ?? null;
        self::$fcmQueue[] = [
            'user_id'   => $userId,
            'title'     => $title,
            'message'   => $message,
            'fcm_token' => $fcmToken,
            'extra'     => $extraData
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
                foreach (self::$fcmQueue as $item) {
                    self::sendFcmNotificationNow($item['user_id'], $item['title'], $item['message'], $item['fcm_token'], $item['extra']);
                }
            });
        }

        return true;
    }

    /**
     * Fetch user details
     */
    private static function getUserDetails(int $userId): ?array {
        try {
            $db = Connection::getInstance();
            return $db->selectOne("SELECT email, first_name, last_name FROM users WHERE id = ? LIMIT 1", [$userId], 'i');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get Court Owner ID by Booking Reference
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
     * Get Court Owner ID by Open Play Registration ID
     */
    private static function getOwnerByOpenPlayId(int $regId): ?array {
        try {
            $db = Connection::getInstance();
            return $db->selectOne("
                SELECT u.id, u.first_name, u.last_name, u.email
                FROM open_play_registrations r
                JOIN open_play_sessions s ON r.session_id = s.id
                JOIN facilities f ON s.facility_id = f.id
                JOIN organizations o ON f.organization_id = o.id
                JOIN users u ON o.owner_id = u.id
                WHERE r.id = ?
                LIMIT 1
            ", [$regId], 'i');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get Platform Admin User IDs
     */
    private static function getAdminUserIds(): array {
        try {
            $db = Connection::getInstance();
            $rows = $db->select("
                SELECT u.id 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE (LOWER(r.name) IN ('admin', 'super_admin', 'superadmin', 'administrator') OR u.role_id = 1)
                  AND u.status = 'active'
            ");

            $ids = [];
            if (!empty($rows)) {
                foreach ($rows as $r) {
                    if (!empty($r['id'])) {
                        $ids[] = (int)$r['id'];
                    }
                }
            }
            return array_unique($ids);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Send FCM Push Notification using Firebase Admin SDK Key
     */
    public static function sendFcmNotificationNow(int $userId, string $title, string $message, ?string $fcmToken, array $extraData = []): void {
        $serviceAccountPath = __DIR__ . '/../../../pikvero-ebd46-firebase-adminsdk-fbsvc-84ea27dad7.json';
        if (!file_exists($serviceAccountPath)) {
            return;
        }

        try {
            $jsonContent = file_get_contents($serviceAccountPath);
            $serviceAccount = json_decode($jsonContent, true);
            if (!$serviceAccount || empty($serviceAccount['private_key'])) {
                return;
            }

            // Generate JWT for Google OAuth2
            $accessToken = self::getGoogleAccessToken($serviceAccount);
            if (!$accessToken) {
                return;
            }

            $projectId = $serviceAccount['project_id'] ?? 'pikvero-ebd46';
            $fcmUrl = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            // Target user's specific FCM token or user topic
            $target = !empty($fcmToken) ? ['token' => $fcmToken] : ['topic' => 'user_' . $userId];

            $payload = [
                'message' => array_merge($target, [
                    'notification' => [
                        'title' => $title,
                        'body'  => $message
                    ],
                    'data' => array_merge([
                        'click_action' => '/pikvero/public/customer/bookings.php',
                        'user_id'      => (string)$userId,
                        'timestamp'    => date('c')
                    ], array_map('strval', $extraData)),
                    'webpush' => [
                        'headers' => [
                            'Urgency' => 'high'
                        ],
                        'notification' => [
                            'title' => $title,
                            'body'  => $message,
                            'icon'  => '/pikvero/assets/images/logo.png',
                            'badge' => '/pikvero/assets/images/logo.png',
                            'vibrate' => [200, 100, 200]
                        ]
                    ]
                ])
            ];

            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $fcmUrl);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                @curl_exec($ch);
                @curl_close($ch);
            }
        } catch (\Throwable $e) {
            // Silently swallow FCM HTTP exceptions
        }
    }

    /**
     * Generate Google Access Token from Service Account Key
     */
    private static function getGoogleAccessToken(array $sa): ?string {
        if (self::$cachedGoogleToken !== null) {
            return self::$cachedGoogleToken;
        }

        try {
            $header = self::base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $now = time();
            $claims = self::base64UrlEncode(json_encode([
                'iss'   => $sa['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud'   => $sa['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'exp'   => $now + 3600,
                'iat'   => $now
            ]));

            $signatureInput = $header . '.' . $claims;
            $privateKey = $sa['private_key'];
            $binarySignature = '';

            if (!openssl_sign($signatureInput, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256)) {
                return null;
            }

            $jwt = $signatureInput . '.' . self::base64UrlEncode($binarySignature);

            // Request access token from Google OAuth2
            if (!function_exists('curl_init')) return null;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $sa['token_uri'] ?? 'https://oauth2.googleapis.com/token');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $res = @curl_exec($ch);
            @curl_close($ch);

            if (!$res) return null;
            $tokenData = json_decode($res, true);
            $token = $tokenData['access_token'] ?? null;
            if ($token) {
                self::$cachedGoogleToken = $token;
            }
            return $token;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
