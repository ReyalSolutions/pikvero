<?php
namespace App\Presentation\Controllers;

use App\Application\Services\AuthService;
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Repositories\OrganizationRepository;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Auth\Auth;
use App\Core\Validation\Validator;
use Exception;

class AuthController {
    private AuthService $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

    public function login(Request $request): void {
        $data = $request->all();
        $identifier = $data['username'] ?? $data['email'] ?? null;

        if (empty($identifier) || empty($data['password'])) {
            Response::error('Username or email and password are required.');
        }

        try {
            $user = $this->authService->login((string)$identifier, (string)$data['password']);
            Response::success('Logged in successfully', $user);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function register(Request $request): void {
        $data = $request->all();
        $validator = new Validator();
        if (!$validator->validate($data, [
            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',
            'email' => 'required|email',
            'phone' => 'required|digits:11',
            'password' => 'required|min:6'
        ])) {

            Response::error('Validation failed', $validator->errors());
        }

        try {
            $user = $this->authService->register($data);
            Response::success('Account registered successfully', $user);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function me(): void {
        $settingRepo = new \App\Infrastructure\Repositories\SystemSettingRepository();
        $settings = $settingRepo->getAllAsMap();

        $appName = $settings['app_name'] ?? 'Pikvero';
        $orgName = $settings['org_name'] ?? 'Pikvero SaaS';
        $logoUrl = $settings['org_logo_url'] ?? '/pikvero/assets/images/logo.png';

        if (!Auth::check()) {
            Response::success('Guest context', [
                'user' => null,
                'role' => 'guest',
                'permissions' => [],
                'organization_id' => null,
                'settings' => [
                    'app_name' => $appName,
                    'org_name' => $orgName,
                    'org_logo_url' => $logoUrl,
                    'currency_symbol' => $settings['currency_symbol'] ?? '₱'
                ]
            ]);
            return;
        }

        $role = Auth::role();
        // Check if logged in user is a court owner / tenant organization user
        if ($role !== 'super_admin' && $role !== 'platform_admin') {
            $orgId = Auth::organizationId();
            if ($orgId) {
                $orgRepo = new \App\Infrastructure\Repositories\OrganizationRepository();
                $org = $orgRepo->findById($orgId);
                if ($org) {
                    if (!empty($org['app_title'])) $appName = $org['app_title'];
                    if (!empty($org['name'])) $orgName = $org['name'];
                    if (!empty($org['logo_url'])) $logoUrl = $org['logo_url'];
                }
            }
        }

        Response::success('User context', [
            'user' => Auth::user(),
            'role' => Auth::role(),
            'permissions' => Auth::permissions(),
            'organization_id' => Auth::organizationId(),
            'settings' => [
                'app_name' => $appName,
                'org_name' => $orgName,
                'org_logo_url' => $logoUrl,
                'currency_symbol' => $settings['currency_symbol'] ?? '₱'
            ]
        ]);
    }

    public function checkUnique(Request $request): void {
        $field = (string)$request->get('field');
        $value = (string)$request->get('value');

        if (!$field || !$value) {
            Response::error('Field and value parameters are required.');
        }

        $userRepo = new UserRepository();
        $orgRepo = new OrganizationRepository();
        $exists = false;

        if ($field === 'username') {
            $exists = ($userRepo->findByUsername($value) !== null);
        } else if ($field === 'email') {
            $exists = ($userRepo->findByEmail($value) !== null);
        } else if ($field === 'phone') {
            $exists = ($userRepo->findByPhone($value) !== null);
        } else if ($field === 'organization_name' || $field === 'orgname') {
            $exists = ($orgRepo->findByName($value) !== null);
        } else if ($field === 'tax_id' || $field === 'taxid') {
            $exists = ($orgRepo->findByTaxId($value) !== null);
        }

        Response::success('Uniqueness check completed', ['field' => $field, 'exists' => $exists]);
    }

    public function logout(): void {
        Auth::logout();
        Response::success('Logged out successfully');
    }
}
