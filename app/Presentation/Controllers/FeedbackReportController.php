<?php
namespace App\Presentation\Controllers;

use App\Core\Auth\Auth;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Infrastructure\Repositories\FeedbackReportRepository;
use Exception;

class FeedbackReportController {
    private FeedbackReportRepository $repo;

    public function __construct() {
        $this->repo = new FeedbackReportRepository();
    }

    /**
     * Submit a new Bug Report or Feature Request (Player or Court Owner)
     */
    public function submitReport(Request $request): void {
        if (!Auth::check()) {
            Response::unauthorized('Please log in to submit a report or feature request.');
            return;
        }

        $userId = (int)Auth::id();
        $type = strtolower(trim((string)$request->get('report_type', 'bug')));
        $title = trim((string)$request->get('title', ''));
        $description = trim((string)$request->get('description', ''));
        $category = trim((string)$request->get('category', 'general'));
        $priority = strtolower(trim((string)$request->get('priority', 'medium')));

        if (!in_array($type, ['bug', 'feature_request'], true)) {
            Response::error('Invalid report type. Allowed types: bug, feature_request.');
            return;
        }

        if (strlen($title) < 4) {
            Response::error('Title must be at least 4 characters long.');
            return;
        }

        if (strlen($description) < 10) {
            Response::error('Description must be at least 10 characters long to help us understand the issue.');
            return;
        }

        if (!in_array($priority, ['low', 'medium', 'high', 'critical'], true)) {
            $priority = 'medium';
        }

        try {
            $reportId = $this->repo->create($userId, $type, $title, $description, $category, $priority);
            $report = $this->repo->getById($reportId);
            Response::success('Your ' . ($type === 'bug' ? 'bug report' : 'feature request') . ' has been submitted successfully!', $report);
        } catch (Exception $e) {
            Response::error('Failed to submit report: ' . $e->getMessage());
        }
    }

    /**
     * List current user's submitted reports and track their status
     */
    public function listUserReports(Request $request): void {
        if (!Auth::check()) {
            Response::unauthorized();
            return;
        }

        $userId = (int)Auth::id();
        $reports = $this->repo->getByUserId($userId);
        Response::success('User feedback reports', $reports);
    }

    /**
     * Admin: List all reports across court owners and players with filters and stats
     */
    public function listAdminReports(Request $request): void {
        $role = Auth::role();
        if ($role !== 'super_admin' && $role !== 'platform_admin' && !Auth::can('system.manage')) {
            Response::forbidden('Access denied. Administrator privileges required.');
            return;
        }

        $filters = [
            'report_type' => $request->get('report_type', 'all'),
            'status' => $request->get('status', 'all'),
            'priority' => $request->get('priority', 'all'),
            'role' => $request->get('role', 'all'),
            'search' => $request->get('search', '')
        ];

        $reports = $this->repo->getAll($filters);
        $stats = $this->repo->getStats();

        Response::success('All feedback reports', [
            'reports' => $reports,
            'stats' => $stats
        ]);
    }

    /**
     * Admin: Update report status, priority, and resolution remarks
     */
    public function updateReportStatus(Request $request): void {
        $role = Auth::role();
        if ($role !== 'super_admin' && $role !== 'platform_admin' && !Auth::can('system.manage')) {
            Response::forbidden('Access denied. Administrator privileges required.');
            return;
        }

        $reportId = (int)$request->get('report_id');
        if (!$reportId) {
            Response::error('Report ID is required.');
            return;
        }

        $status = strtolower(trim((string)$request->get('status', '')));
        $validStatuses = ['pending', 'under_review', 'in_progress', 'resolved', 'declined'];
        if (!in_array($status, $validStatuses, true)) {
            Response::error('Invalid status selected.');
            return;
        }

        $priority = strtolower(trim((string)$request->get('priority', '')));
        if ($priority !== '' && !in_array($priority, ['low', 'medium', 'high', 'critical'], true)) {
            $priority = null;
        }

        $adminResponse = trim((string)$request->get('admin_response', ''));
        $resolvedBy = (int)Auth::id();

        try {
            $success = $this->repo->updateStatus($reportId, $status, $adminResponse, $priority ?: null, $resolvedBy);
            if (!$success) {
                Response::error('Report not found or no changes were made.');
                return;
            }

            $updated = $this->repo->getById($reportId);
            Response::success('Report status updated successfully!', $updated);
        } catch (Exception $e) {
            Response::error('Failed to update report: ' . $e->getMessage());
        }
    }
}
