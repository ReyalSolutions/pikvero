<?php
namespace App\Presentation\Controllers;

use App\Infrastructure\Repositories\FacilityRepository;
use App\Infrastructure\Repositories\CourtRepository;
use App\Infrastructure\Repositories\BookingRepository;
use App\Infrastructure\Repositories\OrganizationRepository;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Auth\Auth;
use App\Core\Validation\Validator;
use Exception;

class OwnerController {
    private FacilityRepository $facilityRepo;
    private CourtRepository $courtRepo;
    private BookingRepository $bookingRepo;
    private OrganizationRepository $orgRepo;

    public function __construct() {
        $this->facilityRepo = new FacilityRepository();
        $this->courtRepo = new CourtRepository();
        $this->bookingRepo = new BookingRepository();
        $this->orgRepo = new OrganizationRepository();
    }

    private function getTenantOrgId(): int {
        if (!Auth::check() || !Auth::hasRole('court_owner', 'super_admin', 'facility_manager', 'platform_admin', 'admin')) {
            Response::forbidden('Access restricted to court owners.');
        }

        $orgId = Auth::organizationId();

        if (!$orgId || $orgId <= 0) {
            $userId = Auth::id();
            if ($userId) {
                try {
                    $db = \App\Core\Database\Connection::getInstance();
                    $org = $db->selectOne("SELECT id FROM organizations WHERE owner_id = ? LIMIT 1", [$userId], 'i');
                    if ($org && !empty($org['id'])) {
                        $orgId = (int)$org['id'];
                    }
                } catch (\Throwable $e) {}
            }
        }

        if (!$orgId || $orgId <= 0) {
            if (Auth::hasRole('super_admin', 'platform_admin', 'admin')) {
                $orgId = 1;
            } else if (Auth::check()) {
                try {
                    $db = \App\Core\Database\Connection::getInstance();
                    $firstOrg = $db->selectOne("SELECT id FROM organizations ORDER BY id ASC LIMIT 1");
                    if ($firstOrg && !empty($firstOrg['id'])) {
                        $orgId = (int)$firstOrg['id'];
                    } else {
                        $orgId = 1;
                    }
                } catch (\Throwable $e) {
                    $orgId = 1;
                }
            }
        }

        if (!$orgId) {
            Response::forbidden('Owner organization profile not found.');
        }

        return (int)$orgId;
    }

    public function getDashboard(): void {
        if (!Auth::hasPermission('organization.view', 'facility.view', 'court.view', 'booking.view', 'bookings.view', 'reports.view', 'report.view', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to view owner dashboard metrics.');
        }

        $orgId = $this->getTenantOrgId();

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate   = $_GET['end_date']   ?? date('Y-m-d');

        $metrics = $this->bookingRepo->getOwnerMetrics($orgId);
        $analytics = $this->bookingRepo->getComprehensiveAnalytics($orgId, $startDate, $endDate);
        
        $resBookings = $this->bookingRepo->getPaginatedBookings($orgId, 1, 10, '', 'all', 'all');
        $recentBookings = $resBookings['data'] ?? [];
        
        $courts = $this->courtRepo->findByOwnerOrganization($orgId);

        Response::success('Owner Dashboard Metrics', [
            'metrics' => $metrics,
            'analytics' => $analytics,
            'recent_bookings' => $recentBookings,
            'total_courts' => count($courts)
        ]);
    }

    public function getFacilities(): void {
        $orgId = $this->getTenantOrgId();
        $facilities = $this->facilityRepo->findByOrganizationId($orgId);
        Response::success('Owner Facilities', $facilities);
    }

    public function createFacility(Request $request): void {
        $orgId = $this->getTenantOrgId();

        // ── Subscription Plan Facility Limit Check ────────────────────────
        $subRepo = new \App\Infrastructure\Repositories\SubscriptionPlanRepository();
        $summary = $subRepo->getTenantSubscriptionSummary($orgId);
        $sub = $summary['subscription'] ?? null;
        $usage = $summary['usage'] ?? ['facilities' => 0];

        if ($sub) {
            $maxFac  = (int)($sub['max_facilities'] ?? 1);
            $currFac = (int)($usage['facilities'] ?? 0);

            if ($currFac >= $maxFac) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'code' => 'PLAN_LIMIT_REACHED',
                    'message' => "Facility creation limit reached for your current subscription plan ({$currFac} / {$maxFac} allowed). Please upgrade your plan tier to add more facilities.",
                    'max_facilities' => $maxFac,
                    'current_facilities' => $currFac,
                    'plan_name' => $sub['plan_name'] ?? 'Current Plan',
                    'redirect_url' => '/pikvero/public/owner/my-plan.php'
                ]);
                exit;
            }
        }
        // ──────────────────────────────────────────────────────────────────

        $data = $request->all();

        $validator = new Validator();
        $rules = [
            'name' => 'required|min:3|max:100',
            'address' => 'required|min:5|max:255',
            'city' => 'required|min:2|max:100'
        ];
        if (!empty($data['email'])) {
            $rules['email'] = 'email';
        }

        if (!$validator->validate($data, $rules)) {
            Response::error('Validation failed', $validator->errors());
        }

        $data['organization_id'] = $orgId;
        $id = $this->facilityRepo->create($data);
        Response::success('Facility created successfully', ['id' => $id]);
    }

    public function updateFacility(Request $request): void {
        $orgId = $this->getTenantOrgId();
        $data = $request->all();

        $validator = new Validator();
        $rules = [
            'facility_id' => 'required|numeric',
            'name' => 'required|min:3|max:100',
            'address' => 'required|min:5|max:255',
            'city' => 'required|min:2|max:100'
        ];
        if (!empty($data['email'])) {
            $rules['email'] = 'email';
        }

        if (!$validator->validate($data, $rules)) {
            Response::error('Validation failed', $validator->errors());
        }

        $facilityId = (int)$data['facility_id'];
        $facility = $this->facilityRepo->findById($facilityId);

        if (!$facility) {
            Response::error('Facility not found.');
        }

        // Verify facility belongs to tenant org (or super admin)
        if (!Auth::hasRole('super_admin') && (int)$facility['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified facility.');
        }

        $this->facilityRepo->update($facilityId, [
            'name' => $data['name'],
            'address' => $data['address'],
            'city' => $data['city'],
            'description' => $data['description'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => $data['status'] ?? 'active'
        ]);

        Response::success('Facility details updated successfully.');
    }

    public function getCourts(): void {
        if (!Auth::hasPermission('court.view', 'courts.view', 'courts.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to view court directory.');
        }

        $orgId = $this->getTenantOrgId();
        $courts = $this->courtRepo->findByOwnerOrganization($orgId);
        Response::success('Owner Courts', $courts);
    }

    public function createCourt(Request $request): void {
        if (!Auth::hasPermission('court.create', 'courts.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to add new courts.');
        }

        $orgId = $this->getTenantOrgId();

        // ── Subscription Plan Court Limit Check ────────────────────────────
        $subRepo = new \App\Infrastructure\Repositories\SubscriptionPlanRepository();
        $summary = $subRepo->getTenantSubscriptionSummary($orgId);
        $sub = $summary['subscription'] ?? null;
        $usage = $summary['usage'] ?? ['courts' => 0];

        if ($sub) {
            $maxCourts  = (int)($sub['max_courts'] ?? 2);
            $currCourts = (int)($usage['courts'] ?? 0);

            if ($currCourts >= $maxCourts) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'code' => 'PLAN_LIMIT_REACHED',
                    'message' => "Court creation limit reached for your current subscription plan ({$currCourts} / {$maxCourts} allowed). Please upgrade your plan tier to add more courts.",
                    'max_courts' => $maxCourts,
                    'current_courts' => $currCourts,
                    'plan_name' => $sub['plan_name'] ?? 'Current Plan',
                    'redirect_url' => '/pikvero/public/owner/my-plan.php'
                ]);
                exit;
            }
        }
        // ──────────────────────────────────────────────────────────────────

        $data = $request->all();

        $validator = new Validator();
        if (!$validator->validate($data, [
            'facility_id' => 'required|numeric',
            'name' => 'required',
            'base_price_per_hour' => 'required|numeric'
        ])) {
            Response::error('Validation failed', $validator->errors());
        }

        // Verify facility belongs to tenant org
        $facility = $this->facilityRepo->findById((int)$data['facility_id']);
        if (!$facility || (int)$facility['organization_id'] !== $orgId) {
            Response::forbidden('Invalid facility selection for your organization.');
        }

        $courtId = $this->courtRepo->create($data);
        Response::success('Court created successfully', ['id' => $courtId]);
    }

    public function updateCourt(Request $request): void {
        if (!Auth::hasPermission('court.update', 'courts.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to update courts.');
        }

        $orgId = $this->getTenantOrgId();
        $data = $request->all();

        $validator = new Validator();
        if (!$validator->validate($data, [
            'court_id' => 'required|numeric',
            'name' => 'required|min:2|max:100',
            'court_type' => 'required',
            'base_price_per_hour' => 'required|numeric'
        ])) {
            Response::error('Validation failed', $validator->errors());
        }

        $courtId = (int)$data['court_id'];
        $court = $this->courtRepo->findById($courtId);

        if (!$court) {
            Response::error('Court not found.');
        }

        // Verify court belongs to tenant org (or super admin)
        if (!Auth::hasRole('super_admin') && (int)$court['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified court.');
        }

        $this->courtRepo->update($courtId, [
            'name' => $data['name'],
            'court_type' => $data['court_type'] ?? 'outdoor',
            'surface_type' => $data['surface_type'] ?? 'cushioned_acrylic',
            'base_price_per_hour' => (float)$data['base_price_per_hour'],
            'status' => $data['status'] ?? 'active'
        ]);

        Response::success('Court details updated successfully.');
    }

    public function getPricingRules(Request $request): void {
        if (!Auth::hasPermission('pricing.manage', 'court.view', 'courts.view', 'courts.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to view pricing rules.');
        }

        $orgId = $this->getTenantOrgId();
        $courtId = (int)($request->get('court_id') ?? 0);
        if (!$courtId) Response::error('court_id is required.');

        $court = $this->courtRepo->findById($courtId);
        if (!$court) Response::error('Court not found.');
        if (!Auth::hasRole('super_admin') && (int)$court['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified court.');
        }

        $rules = $this->courtRepo->getPricingRules($courtId);
        Response::success('Court pricing rules', $rules);
    }

    public function getCourtDetail(Request $request): void {
        if (!Auth::hasPermission('court.view', 'courts.view', 'courts.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to view court details.');
        }

        $orgId   = $this->getTenantOrgId();
        $courtId = (int)($request->get('court_id') ?? 0);
        if (!$courtId) Response::error('court_id is required.');

        $court = $this->courtRepo->findById($courtId);
        if (!$court) Response::error('Court not found.');
        if (!Auth::hasRole('super_admin') && (int)$court['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified court.');
        }

        $pricing        = $this->courtRepo->getPricingRules($courtId);
        $blockouts      = $this->courtRepo->getAllBlockedSchedules($courtId);
        $operatingHours = $this->courtRepo->getOperatingHours($courtId);

        Response::success('Court detail', [
            'court'          => $court,
            'pricing'        => $pricing,
            'blockouts'      => $blockouts,
            'operating_hours'=> $operatingHours,
        ]);
    }

    public function addPricingRule(Request $request): void {
        if (!Auth::hasPermission('pricing.manage', 'court.update', 'courts.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to configure pricing rules.');
        }

        $orgId = $this->getTenantOrgId();
        $data = $request->all();

        $validator = new Validator();
        if (!$validator->validate($data, [
            'court_id' => 'required|numeric',
            'name' => 'required|max:50',
            'price_per_hour' => 'required|numeric',
            'start_time' => 'required',
            'end_time' => 'required'
        ])) {
            Response::error('Validation failed', $validator->errors());
        }

        $court = $this->courtRepo->findById((int)$data['court_id']);
        if (!$court) Response::error('Court not found.');
        if (!Auth::hasRole('super_admin') && (int)$court['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified court.');
        }

        $id = $this->courtRepo->addPricingRule($data);
        Response::success('Pricing rule added successfully.', ['id' => $id]);
    }

    public function updatePricingRule(Request $request): void {
        if (!Auth::hasPermission('pricing.manage', 'court.update', 'courts.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to update pricing rules.');
        }

        $orgId = $this->getTenantOrgId();
        $data = $request->all();

        $validator = new Validator();
        if (!$validator->validate($data, [
            'rule_id' => 'required|numeric',
            'name' => 'required|max:50',
            'price_per_hour' => 'required|numeric',
            'start_time' => 'required',
            'end_time' => 'required'
        ])) {
            Response::error('Validation failed', $validator->errors());
        }

        $rule = $this->courtRepo->getPricingRuleById((int)$data['rule_id']);
        if (!$rule) Response::error('Pricing rule not found.');

        $court = $this->courtRepo->findById((int)$rule['court_id']);
        if (!Auth::hasRole('super_admin') && (int)$court['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified court.');
        }

        $this->courtRepo->updatePricingRule((int)$data['rule_id'], $data);
        Response::success('Pricing rule updated successfully.');
    }

    public function deletePricingRule(Request $request): void {
        if (!Auth::hasPermission('pricing.manage', 'court.update', 'courts.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to delete pricing rules.');
        }

        $orgId = $this->getTenantOrgId();
        $ruleId = (int)($request->get('rule_id') ?? $request->all()['rule_id'] ?? 0);
        if (!$ruleId) Response::error('rule_id is required.');

        $rule = $this->courtRepo->getPricingRuleById($ruleId);
        if (!$rule) Response::error('Pricing rule not found.');

        $court = $this->courtRepo->findById((int)$rule['court_id']);
        if (!Auth::hasRole('super_admin') && (int)$court['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified court.');
        }

        $this->courtRepo->deletePricingRule($ruleId);
        Response::success('Pricing rule deleted.');
    }

    public function blockSchedule(Request $request): void {
        if (!Auth::hasPermission('availability.manage', 'court.update', 'courts.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to manage court blockout schedules.');
        }
        $orgId = $this->getTenantOrgId();
        $data = $request->all();

        $validator = new Validator();
        if (!$validator->validate($data, [
            'court_id' => 'required|numeric',
            'block_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required'
        ])) {
            Response::error('Validation failed', $validator->errors());
        }

        // Verify court belongs to tenant org
        $court = $this->courtRepo->findById((int)$data['court_id']);
        if (!$court || (int)$court['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified court.');
        }

        $id = $this->courtRepo->addBlockedSchedule(
            (int)$data['court_id'],
            $data['block_date'],
            $data['start_time'],
            $data['end_time'],
            $data['reason'] ?? 'Blocked by Owner'
        );

        Response::success('Schedule blocked successfully', ['id' => $id]);
    }

    public function getBookings(Request $request): void {
        $orgId = $this->getTenantOrgId();
        $page = (int)($request->get('page') ?? 1);
        $limit = (int)($request->get('limit') ?? 10);
        $search = (string)($request->get('search') ?? '');
        $status = (string)($request->get('status') ?? '');
        $resType = (string)($request->get('res_type') ?? $request->get('reservation_type') ?? 'all');

        $result = $this->bookingRepo->getPaginatedBookings($orgId, $page, $limit, $search, $status, $resType);
        Response::success('Owner Bookings Data', $result);
    }

    public function searchCustomers(Request $request): void {
        $this->getTenantOrgId(); // auth gate
        $query = trim($request->get('q') ?? '');
        if (strlen($query) < 2) {
            Response::success('Customer search results', []);
            return;
        }
        $results = $this->bookingRepo->searchCustomers($query);
        Response::success('Customer search results', $results);
    }

    public function createManualBooking(Request $request): void {
        $orgId = $this->getTenantOrgId();
        $data  = $request->all();

        $validator = new Validator();
        if (!Auth::hasPermission('booking.create', 'bookings.manage')) {
            Response::forbidden('Permission denied: You do not have permission to create court reservations.');
        }

        if (!$validator->validate($data, [
            'court_id'     => 'required|numeric',
            'customer_id'  => 'required|numeric',
            'booking_date' => 'required|date',
            'start_time'   => 'required',
            'end_time'     => 'required',
        ])) {
            Response::error('Validation failed', $validator->errors());
        }

        $courtId    = (int)$data['court_id'];
        $customerId = (int)$data['customer_id'];
        $date       = $data['booking_date'];
        $start      = $data['start_time'];   // e.g. "09:00:00"
        $end        = $data['end_time'];     // e.g. "11:00:00"

        // Court ownership check
        $court = $this->courtRepo->findById($courtId);
        if (!$court) Response::error('Court not found.');
        if (!Auth::hasRole('super_admin') && (int)$court['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified court.');
        }

        // Overlap check
        if ($this->bookingRepo->checkOverlapping($courtId, $date, $start, $end)) {
            Response::error('This time slot overlaps with an existing booking. Please choose another time.');
        }

        // Duration & amount
        $startTs = strtotime("$date $start");
        $endTs   = strtotime("$date $end");
        if ($endTs <= $startTs) Response::error('End time must be after start time.');

        $durationHours = ($endTs - $startTs) / 3600;
        $ratePerHour   = (float)($data['rate_per_hour'] ?? $court['base_price_per_hour']);
        $totalAmount   = round($durationHours * $ratePerHour, 2);

        // Booking reference
        $ref = 'MNL-' . strtoupper(substr(md5(uniqid()), 0, 8));

        $source = !empty($data['booking_source']) ? trim($data['booking_source']) : 'Walk-in';
        $userNotes = !empty($data['notes']) ? trim($data['notes']) : '';
        $finalNotes = "[Source: {$source}] " . $userNotes;

        $bookingId = $this->bookingRepo->create([
            'booking_reference' => $ref,
            'customer_id'       => $customerId,
            'court_id'          => $courtId,
            'facility_id'       => (int)$court['facility_id'],
            'organization_id'   => $orgId,
            'booking_date'      => $date,
            'start_time'        => $start,
            'end_time'          => $end,
            'duration_hours'    => $durationHours,
            'rate_per_hour'     => $ratePerHour,
            'total_amount'      => $totalAmount,
            'payment_status'    => $data['payment_status'] ?? 'unpaid',
            'booking_status'    => $data['booking_status'] ?? 'confirmed',
            'notes'             => $finalNotes,
        ]);

        Response::success('Reservation created successfully.', [
            'booking_id'        => $bookingId,
            'booking_reference' => $ref,
            'total_amount'      => $totalAmount,
        ]);
    }

    public function getBookingDetail(Request $request): void {
        if (!Auth::hasPermission('booking.view', 'bookings.view', 'bookings.manage')) {
            Response::forbidden('Permission denied: You do not have permission to view reservation details.');
        }

        $orgId = $this->getTenantOrgId();
        $bookingId = (int)($request->get('booking_id') ?? 0);
        if (!$bookingId) Response::error('booking_id is required.');

        $booking = $this->bookingRepo->getBookingDetail($bookingId);
        if (!$booking) Response::error('Booking record not found.');

        if (!Auth::hasRole('super_admin') && (int)$booking['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified booking.');
        }

        Response::success('Booking Detail', $booking);
    }

    public function updateBookingPayment(Request $request): void {
        if (!Auth::hasPermission('booking.update', 'payment.create', 'payments.manage', 'bookings.manage')) {
            Response::forbidden('Permission denied: You do not have permission to process payments.');
        }

        $orgId = $this->getTenantOrgId();
        $data = $request->all();

        $bookingId = (int)($data['booking_id'] ?? 0);
        $paymentStatus = trim($data['payment_status'] ?? '');
        $note = isset($data['note']) ? trim($data['note']) : null;

        if (!$bookingId) Response::error('booking_id is required.');
        if (!in_array($paymentStatus, ['paid', 'unpaid', 'pending'], true)) {
            Response::error('Invalid payment_status value.');
        }

        $booking = $this->bookingRepo->findById($bookingId);
        if (!$booking) Response::error('Booking not found.');
        if (!Auth::hasRole('super_admin') && (int)$booking['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified booking.');
        }

        $success = $this->bookingRepo->updatePaymentStatus($bookingId, $paymentStatus, $note);
        if ($success) {
            if ($paymentStatus === 'paid') {
                $customerId = (int)($booking['customer_id'] ?? 0);
                $bookingRef = $booking['booking_reference'] ?? "ID #{$bookingId}";
                $facilityName = $booking['facility_name'] ?? 'Facility';
                $courtName = $booking['court_name'] ?? 'Court';
                $bookingDate = $booking['booking_date'] ?? date('Y-m-d');
                $startTime = $booking['start_time'] ?? '00:00:00';
                $endTime = $booking['end_time'] ?? '00:00:00';
                $amount = (float)($booking['total_amount'] ?? 0);
                $pm = $booking['payment_method'] ?? 'Cash';

                if ($customerId > 0) {
                    \App\Infrastructure\Services\PushNotificationService::sendPaymentAddedNotification(
                        $customerId,
                        $bookingRef,
                        $facilityName,
                        $courtName,
                        $amount,
                        $pm
                    );
                    \App\Infrastructure\Services\EmailNotificationService::sendPaymentAddedReceipt(
                        $customerId,
                        $bookingRef,
                        $facilityName,
                        $courtName,
                        $bookingDate,
                        $startTime,
                        $endTime,
                        $amount,
                        $pm
                    );
                }
            }

            Response::success('Payment status updated successfully.', [
                'booking_id' => $bookingId,
                'payment_status' => $paymentStatus
            ]);
        } else {
            Response::error('Failed to update payment status.');
        }
    }

    public function cancelBooking(Request $request): void {
        if (!Auth::hasPermission('booking.cancel', 'bookings.manage')) {
            Response::forbidden('Permission denied: You do not have permission to cancel reservations.');
        }

        $orgId = $this->getTenantOrgId();
        $data = $request->all();

        $bookingId = (int)($data['booking_id'] ?? 0);
        $reason = isset($data['reason']) ? trim($data['reason']) : null;

        if (!$bookingId) Response::error('booking_id is required.');

        $booking = $this->bookingRepo->findById($bookingId);
        if (!$booking) Response::error('Booking record not found.');
        if (!Auth::hasRole('super_admin') && (int)$booking['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified booking.');
        }

        $userId = Auth::id();
        $success = $this->bookingRepo->cancelBooking($bookingId, $reason, $userId);

        if ($success) {
            Response::success('Reservation cancelled successfully.');
        } else {
            Response::error('Failed to cancel reservation.');
        }
    }

    public function refundBooking(Request $request): void {
        if (!Auth::hasPermission('payment.refund', 'payments.manage', 'bookings.manage')) {
            Response::forbidden('Permission denied: You do not have permission to process refunds.');
        }

        $orgId = $this->getTenantOrgId();
        $data = $request->all();

        $bookingId = (int)($data['booking_id'] ?? 0);
        $reason = isset($data['reason']) ? trim($data['reason']) : null;

        if (!$bookingId) Response::error('booking_id is required.');

        $booking = $this->bookingRepo->findById($bookingId);
        if (!$booking) Response::error('Booking record not found.');
        if (!Auth::hasRole('super_admin') && (int)$booking['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified booking.');
        }

        $userId = Auth::id();
        $success = $this->bookingRepo->refundBooking($bookingId, $reason, $userId);

        if ($success) {
            $customerId = (int)($booking['customer_id'] ?? 0);
            $bookingRef = $booking['booking_reference'] ?? "ID #{$bookingId}";
            $facilityName = $booking['facility_name'] ?? 'Facility';
            $courtName = $booking['court_name'] ?? 'Court';
            $bookingDate = $booking['booking_date'] ?? date('Y-m-d');
            $startTime = $booking['start_time'] ?? '00:00:00';
            $endTime = $booking['end_time'] ?? '00:00:00';
            $refundAmount = (float)($booking['total_amount'] ?? 0);

            if ($customerId > 0) {
                \App\Infrastructure\Services\PushNotificationService::sendRefundNotification(
                    $customerId,
                    $bookingRef,
                    $facilityName,
                    $courtName,
                    $refundAmount,
                    (string)$reason
                );
                \App\Infrastructure\Services\EmailNotificationService::sendRefundConfirmation(
                    $customerId,
                    $bookingRef,
                    $facilityName,
                    $courtName,
                    $bookingDate,
                    $startTime,
                    $endTime,
                    $refundAmount,
                    (string)$reason
                );
            }

            Response::success('Reservation refunded successfully.');
        } else {
            Response::error('Failed to refund reservation.');
        }
    }

    public function getCourtAvailability(Request $request): void {
        $courtId = (int)($request->get('court_id') ?? 0);
        $date = trim($request->get('date') ?? '');

        if (!$courtId || !$date) {
            Response::error('court_id and date are required.');
        }

        $activeBookings = $this->bookingRepo->getActiveBookingsForCourtDate($courtId, $date);

        Response::success('Court Availability', [
            'court_id' => $courtId,
            'date' => $date,
            'active_bookings' => $activeBookings
        ]);
    }




    public function getReports(Request $request): void {
        $role = Auth::role();
        $orgId = ($role === 'super_admin' || $role === 'platform_admin') ? null : $this->getTenantOrgId();

        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate   = $request->get('end_date', date('Y-m-d'));

        $reports = $this->bookingRepo->getComprehensiveAnalytics($orgId, $startDate, $endDate);
        Response::success('Owner Analytics & Reports', $reports);
    }

    public function getSettings(): void {
        $orgId = $this->getTenantOrgId();
        $org = $this->orgRepo->findById($orgId);
        if (!$org) {
            Response::error('Organization profile not found.');
        }
        Response::success('Owner Organization Branding Settings', $org);
    }

    public function updateSettings(Request $request): void {
        $orgId = $this->getTenantOrgId();
        $data = $request->all();

        $org = $this->orgRepo->findById($orgId);
        if (!$org) {
            Response::error('Organization profile not found.');
        }

        $validator = new Validator();
        if (!$validator->validate($data, [
            'name' => 'required|max:100'
        ])) {
            Response::error('Validation failed', $validator->errors());
        }

        $this->orgRepo->updateBranding($orgId, [
            'name' => $data['name'],
            'app_title' => $data['app_title'] ?? null,
            'logo_url' => $data['logo_url'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'description' => $data['description'] ?? null
        ]);

        Response::success('Organization branding settings updated successfully.');
    }

    public function uploadLogo(Request $request): void {
        $orgId = $this->getTenantOrgId();

        if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            Response::error('Please select a valid logo image file to upload.');
        }

        $file = $_FILES['logo'];
        $maxSizeBytes = 5 * 1024 * 1024; // 5 MB

        if ($file['size'] > $maxSizeBytes) {
            Response::error('Logo file size exceeds maximum 5MB limit.');
        }

        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/svg+xml', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes, true)) {
            Response::error('Invalid file format. Please upload a valid image file (PNG, JPG, WEBP, SVG, GIF).');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (empty($ext)) $ext = 'png';

        $uploadDir = __DIR__ . '/../../../assets/images/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'org_logo_' . $orgId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            Response::error('Failed to save uploaded logo file.');
        }

        $relativeUrl = '/pikvero/assets/images/uploads/' . $filename;
        
        $org = $this->orgRepo->findById($orgId);
        if ($org) {
            $org['logo_url'] = $relativeUrl;
            $this->orgRepo->updateBranding($orgId, $org);
        }

        Response::success('Organization logo uploaded successfully.', [
            'logo_url' => $relativeUrl
        ]);
    }

    public function uploadCourtImage(Request $request): void {
        if (!Auth::hasPermission('court.update', 'courts.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to manage court images.');
        }

        $orgId = $this->getTenantOrgId();
        $courtId = (int)($request->get('court_id') ?? $_POST['court_id'] ?? 0);
        if (!$courtId) Response::error('court_id is required.');

        $court = $this->courtRepo->findById($courtId);
        if (!$court) Response::error('Court not found.');
        if (!Auth::hasRole('super_admin') && (int)$court['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified court.');
        }

        // Limit to max 10 images per court
        $count = $this->courtRepo->getCourtImageCount($courtId);
        if ($count >= 10) {
            Response::error('Maximum limit reached: A court can have a maximum of 10 photos.');
        }

        $imageUrl = '';

        // Check if file upload or image_url text input
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/avif'];
            if (!in_array(mime_content_type($file['tmp_name']), $allowedTypes, true)) {
                Response::error('Invalid image file format. Only JPG, PNG, WEBP, and AVIF are allowed.');
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                Response::error('Image file size must be less than 5MB.');
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (empty($ext)) $ext = 'jpg';

            $uploadDir = __DIR__ . '/../../../assets/images/courts';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = 'court_' . $courtId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $targetPath = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                Response::error('Failed to save uploaded image file.');
            }
            $imageUrl = '/pikvero/assets/images/courts/' . $filename;
        } else {
            $urlInput = trim($request->get('image_url') ?? $_POST['image_url'] ?? '');
            if (!empty($urlInput)) {
                $imageUrl = $urlInput;
            } else {
                Response::error('Please select an image file or provide a valid image URL.');
            }
        }

        $imageId = $this->courtRepo->addCourtImage($courtId, $imageUrl);
        Response::success('Court photo added successfully.', [
            'id' => $imageId,
            'court_id' => $courtId,
            'image_path' => $imageUrl
        ]);
    }

    public function deleteCourtImage(Request $request): void {
        if (!Auth::hasPermission('court.update', 'courts.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to delete court images.');
        }

        $orgId = $this->getTenantOrgId();
        $data = $request->all();
        $imageId = (int)($data['image_id'] ?? $request->get('image_id') ?? 0);
        if (!$imageId) Response::error('image_id is required.');

        $image = $this->courtRepo->getCourtImageById($imageId);
        if (!$image) Response::error('Court image not found.');

        if (!Auth::hasRole('super_admin') && (int)$image['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified court image.');
        }

        $this->courtRepo->deleteCourtImage($imageId);
        Response::success('Court photo deleted successfully.');
    }

    public function uploadFacilityImage(Request $request): void {
        if (!Auth::hasPermission('facility.update', 'facilities.manage', 'system.manage') && !Auth::hasRole('court_owner', 'super_admin', 'platform_admin', 'facility_manager')) {
            Response::forbidden('Permission denied: You do not have permission to manage facility images.');
        }

        $orgId = $this->getTenantOrgId();
        $facilityId = (int)($request->get('facility_id') ?? $_POST['facility_id'] ?? 0);
        if (!$facilityId) Response::error('facility_id is required.');

        $facility = $this->facilityRepo->findById($facilityId);
        if (!$facility) Response::error('Facility not found.');
        if (!Auth::hasRole('super_admin') && (int)$facility['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified facility.');
        }

        // Limit to max 10 images per facility
        $count = $this->facilityRepo->getFacilityImageCount($facilityId);
        if ($count >= 10) {
            Response::error('Maximum limit reached: A facility can have a maximum of 10 photos.');
        }

        $imageUrl = '';

        // Check if file upload or image_url text input
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/avif'];
            if (!in_array(mime_content_type($file['tmp_name']), $allowedTypes, true)) {
                Response::error('Invalid image file format. Only JPG, PNG, WEBP, and AVIF are allowed.');
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                Response::error('Image file size must be less than 5MB.');
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (empty($ext)) $ext = 'jpg';

            $uploadDir = __DIR__ . '/../../../assets/images/facilities';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = 'facility_' . $facilityId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $targetPath = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                Response::error('Failed to save uploaded image file.');
            }
            $imageUrl = '/pikvero/assets/images/facilities/' . $filename;
        } else {
            $urlInput = trim($request->get('image_url') ?? $_POST['image_url'] ?? '');
            if (!empty($urlInput)) {
                $imageUrl = $urlInput;
            } else {
                Response::error('Please select an image file or provide a valid image URL.');
            }
        }

        $imageId = $this->facilityRepo->addFacilityImage($facilityId, $imageUrl);
        Response::success('Facility photo added successfully.', [
            'id' => $imageId,
            'facility_id' => $facilityId,
            'image_path' => $imageUrl
        ]);
    }

    public function deleteFacilityImage(Request $request): void {
        if (!Auth::hasPermission('facility.update', 'facilities.manage', 'system.manage') && !Auth::hasRole('court_owner', 'super_admin', 'platform_admin', 'facility_manager')) {
            Response::forbidden('Permission denied: You do not have permission to delete facility images.');
        }

        $orgId = $this->getTenantOrgId();
        $data = $request->all();
        $imageId = (int)($data['image_id'] ?? $request->get('image_id') ?? 0);
        if (!$imageId) Response::error('image_id is required.');

        $image = $this->facilityRepo->getFacilityImageById($imageId);
        if (!$image) Response::error('Facility image not found.');

        if (!Auth::hasRole('super_admin') && (int)$image['organization_id'] !== $orgId) {
            Response::forbidden('Access denied to specified facility image.');
        }

        $this->facilityRepo->deleteFacilityImage($imageId);
        Response::success('Facility photo deleted successfully.');
    }
}
