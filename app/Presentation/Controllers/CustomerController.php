<?php
namespace App\Presentation\Controllers;

use App\Infrastructure\Repositories\FacilityRepository;
use App\Infrastructure\Repositories\CourtRepository;
use App\Infrastructure\Repositories\BookingRepository;
use App\Application\Services\AvailabilityEngine;
use App\Application\Services\BookingService;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Auth\Auth;
use App\Core\Validation\Validator;
use Exception;

class CustomerController {
    private FacilityRepository $facilityRepo;
    private CourtRepository $courtRepo;
    private BookingRepository $bookingRepo;
    private AvailabilityEngine $availabilityEngine;
    private BookingService $bookingService;

    public function __construct() {
        $this->facilityRepo = new FacilityRepository();
        $this->courtRepo = new CourtRepository();
        $this->bookingRepo = new BookingRepository();
        $this->availabilityEngine = new AvailabilityEngine();
        $this->bookingService = new BookingService();
    }

    public function getFacilities(): void {
        $facilities = $this->facilityRepo->getAllActive();
        Response::success('Active facilities loaded', $facilities);
    }

    public function getCities(): void {
        $cities = $this->facilityRepo->getAllCities();
        Response::success('Active cities loaded', $cities);
    }

    public function getHomeData(): void {
        // Top 4 active courts with most reservations
        $featuredCourts = $this->courtRepo->getMostReservedCourts(4);
        foreach ($featuredCourts as &$c) {
            $c['images'] = $this->courtRepo->getCourtImages((int)$c['id']);
        }
        unset($c);

        // Popular locations: distinct cities with active court counts
        $facilities = $this->facilityRepo->getAllActive();
        $locations = [];
        foreach ($facilities as $f) {
            $city = $f['city'];
            if (!isset($locations[$city])) {
                $locations[$city] = ['city' => $city, 'court_count' => 0];
            }
            $locations[$city]['court_count'] += (int)($f['total_courts'] ?? 0);
        }
        $locations = array_values($locations);

        // Featured facilities (up to 6)
        $featuredFacilities = array_slice($facilities, 0, 6);
        foreach ($featuredFacilities as &$f) {
            $f['images'] = $this->facilityRepo->getFacilityImages((int)$f['id']);
        }
        unset($f);

        // Popular Packages
        $packageRepo = new \App\Infrastructure\Repositories\PackageRepository();
        $packages = $packageRepo->getAllActive();

        Response::success('Homepage data', [
            'most_booked_court'   => !empty($featuredCourts) ? $featuredCourts[0] : null,
            'featured_courts'     => $featuredCourts,
            'locations'           => $locations,
            'popular_cities'      => $this->facilityRepo->getPopularCities(6),
            'featured_facilities' => $featuredFacilities,
            'packages'            => $packages,
        ]);
    }

    public function getFacilityDetail(Request $request): void {
        $id = (int)$request->get('id');
        $facility = $this->facilityRepo->findById($id);
        if (!$facility) {
            Response::notFound('Facility not found');
        }

        $facility['images'] = $this->facilityRepo->getFacilityImages($id);

        $courts = $this->courtRepo->findByFacilityId($id);
        foreach ($courts as &$court) {
            $court['images'] = $this->courtRepo->getCourtImages((int)$court['id']);
        }
        unset($court);

        $amenities = $this->facilityRepo->getFacilityAmenities($id);

        $settingRepo = new \App\Infrastructure\Repositories\SystemSettingRepository();
        $allSettings = $settingRepo->getAllAsMap();

        $productRepo = new \App\Infrastructure\Repositories\ProductRepository();
        $productsResult = $productRepo->getPaginatedProducts(0, 12, '', $id);
        $products = $productsResult['data'] ?? [];

        Response::success('Facility detail', [
            'facility'  => $facility,
            'courts'    => $courts,
            'amenities' => $amenities,
            'products'  => $products,
            'payment_methods' => array_values(array_filter(['qrph'], function ($method) use ($allSettings) {
                $key = 'paymongo_enable_' . $method;
                return !isset($allSettings[$key]) || $allSettings[$key] === '1';
            })),
            'fees'      => [
                'platform_fee_percent' => (float)($allSettings['platform_commission_pct'] ?? $allSettings['platform_fee_percent'] ?? 0),
                'paymongo_fee_percent' => (float)($allSettings['paymongo_fee_percent'] ?? 0),
                'pass_gateway_fee' => ($allSettings['payment_gateway_fee_pass'] ?? '0') === '1'
            ]
        ]);
    }

    public function searchCourts(Request $request): void {
        $filters = [
            'search'      => $request->get('search') ?? $request->get('q'),
            'facility_id' => $request->get('facility_id'),
            'court_id'    => $request->get('court_id'),
            'court_type'  => $request->get('court_type'),
            'city'        => $request->get('city'),
            'max_price'   => $request->get('max_price'),
            'lighting'    => $request->get('lighting'),
            'parking'     => $request->get('parking'),
            'gear'        => $request->get('gear'),
            'shower'      => $request->get('shower'),
            'open_now'    => $request->get('open_now')
        ];
        $courts = $this->courtRepo->search($filters);

        // Attach real amenities and images to each court
        foreach ($courts as &$court) {
            $court['amenities'] = $this->courtRepo->getCourtAmenities((int)$court['id']);
            $facilityAmenities = $this->facilityRepo->getFacilityAmenities((int)$court['facility_id']);
            $court['amenities'] = array_values(array_reduce(array_merge($court['amenities'], $facilityAmenities), function ($items, $amenity) {
                $items[$amenity['name']] = $amenity;
                return $items;
            }, []));
            $court['images']    = $this->courtRepo->getCourtImages((int)$court['id']);
        }
        unset($court);

        Response::success('Courts loaded', $courts);
    }

    public function getAvailability(Request $request): void {
        $courtId = (int)$request->get('court_id');
        $date = $request->get('date', date('Y-m-d'));

        if (!$courtId) {
            Response::error('Court ID is required');
        }

        $slots = $this->availabilityEngine->getAvailableTimeSlots($courtId, $date);
        Response::success('Availability slots', [
            'court_id' => $courtId,
            'date' => $date,
            'slots' => $slots
        ]);
    }

    public function createBooking(Request $request): void {
        if (!Auth::check()) {
            Response::unauthorized('Please log in to reserve a court.');
        }

        $data = $request->all();
        $validator = new Validator();
        if (!$validator->validate($data, [
            'court_id' => 'required|numeric',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required'
        ])) {
            Response::error('Validation failed', $validator->errors());
        }

        try {
            $paymentMethod = strtolower(trim($data['payment_method'] ?? 'cash'));
            if (!in_array($paymentMethod, ['cash', 'online'])) {
                $paymentMethod = 'cash';
            }

            $booking = $this->bookingService->createBooking(
                Auth::id(),
                (int)$data['court_id'],
                $data['date'],
                $data['start_time'],
                $data['end_time'],
                $data['notes'] ?? null,
                $paymentMethod,
                is_array($data['addons'] ?? null) ? $data['addons'] : []
            );
            Response::success('Reservation created successfully!', $booking);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function getMyBookings(Request $request): void {
        if (!Auth::check()) {
            Response::unauthorized();
        }

        $userId = Auth::id();

        if ($request->get('draw') !== null) {
            $draw = (int)($request->get('draw') ?? 1);
            $start = (int)($request->get('start') ?? 0);
            $length = (int)($request->get('length') ?? 10);
            if ($length < 1) $length = 10;
            if ($length > 100) $length = 100;

            $searchArray = $request->get('search');
            $search = is_array($searchArray) ? (string)($searchArray['value'] ?? '') : (string)($request->get('search') ?? '');

            $orderArray = $request->get('order');
            $orderColIndex = is_array($orderArray) ? (string)($orderArray[0]['column'] ?? '0') : '0';
            $orderDir = is_array($orderArray) ? (string)($orderArray[0]['dir'] ?? 'DESC') : 'DESC';

            $statusFilter = (string)($request->get('status') ?? 'all');
            $startDate    = (string)($request->get('start_date') ?? '');
            $endDate      = (string)($request->get('end_date') ?? '');

            $res = $this->bookingRepo->getCustomerBookingsDataTables(
                $userId,
                $start,
                $length,
                $search,
                $orderColIndex,
                $orderDir,
                $statusFilter,
                $startDate,
                $endDate,
                (string)($request->get('view') ?? '')
            );

            header('Content-Type: application/json');
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $res['recordsTotal'],
                'recordsFiltered' => $res['recordsFiltered'],
                'data' => $res['data']
            ]);
            exit;
        }

        $bookings = $this->bookingRepo->getCustomerBookings($userId);
        Response::success('Customer bookings', $bookings);
    }

    public function cancelBooking(Request $request): void {
        if (!Auth::check()) {
            Response::unauthorized();
        }

        $bookingId = (int)$request->get('booking_id');
        $reason = trim((string)($request->get('reason') ?? $request->get('notes') ?? ''));
        try {
            $this->bookingService->cancelBooking($bookingId, Auth::id(), $reason);
            Response::success('Booking cancelled successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
}
