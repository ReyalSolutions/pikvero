<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;
use App\Core\Http\Response;

try {
    $db = Connection::getInstance();
    $plans = $db->select("SELECT id, name, slug, monthly_price, yearly_price, is_free_trial, max_facilities, max_courts, max_staff, description FROM subscription_plans ORDER BY monthly_price ASC, id ASC");

    foreach ($plans as &$plan) {
        $features = $db->select("SELECT feature FROM subscription_plan_features WHERE plan_id = ? ORDER BY id ASC", [(int)$plan['id']], 'i');
        $plan['features'] = array_column($features, 'feature');
        $plan['monthly_price']  = (float)$plan['monthly_price'];
        $plan['yearly_price']   = (float)$plan['yearly_price'];
        $plan['is_free_trial']  = (int)$plan['is_free_trial'];
        $plan['max_facilities'] = (int)$plan['max_facilities'];
        $plan['max_courts']     = (int)$plan['max_courts'];
        $plan['max_staff']      = (int)$plan['max_staff'];
    }

    Response::success('Subscription plans retrieved successfully', $plans);
} catch (\Throwable $e) {
    Response::error('Failed to retrieve subscription plans: ' . $e->getMessage());
}
