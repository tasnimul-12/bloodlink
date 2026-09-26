<?php
/**
 * BloodLink - Authentication Controller
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../services/AuthService.php';

class AuthController extends Controller {
    public function login(): void {
        $this->render('public/login', [
            'title' => 'Sign In — BloodLink'
        ]);
    }

    public function doLogin(): void {
        $this->validateCsrf();

        $login = $this->request->input('login', '');
        $password = $this->request->input('password', '');

        $result = AuthService::authenticate($login, $password);

        if (!$result['success']) {
            Session::setFlash('danger', $result['message']);
            $this->redirect('/login');
            return;
        }

        $user = $result['user'];
        Session::setFlash('success', "Welcome back, {$user['username']}!");

        if ($user['role_name'] === 'ADMIN') {
            $this->redirect('/admin/dashboard');
        } elseif ($user['role_name'] === 'HOSPITAL_STAFF') {
            $this->redirect('/hospital/dashboard');
        } elseif ($user['role_name'] === 'DONOR') {
            $this->redirect('/donor/dashboard');
        } else {
            $this->redirect('/');
        }
    }

    public function logout(): void {
        if (Session::isLoggedIn()) {
            $user = Session::user();
            AuditService::log('LOGOUT', 'users', $user['user_id'], null, null, $user['user_id']);
        }
        Session::destroy();
        Session::start();
        Session::setFlash('info', 'You have been safely signed out.');
        $this->redirect('/login');
    }

    public function registerDonor(): void {
        $pdo = Database::getConnection();
        $bloodGroups = $pdo->query("SELECT * FROM blood_groups ORDER BY blood_group_id ASC")->fetchAll();

        $this->render('public/register_donor', [
            'title' => 'Register as Blood Donor — BloodLink',
            'bloodGroups' => $bloodGroups
        ]);
    }

    public function doRegisterDonor(): void {
        $this->validateCsrf();

        $data = $this->request->all();
        $result = AuthService::registerDonor($data);

        if (!$result['success']) {
            Session::setFlash('danger', $result['message']);
            $this->redirect('/register/donor');
            return;
        }

        Session::setFlash('success', $result['message']);
        $this->redirect('/login');
    }

    public function registerHospital(): void {
        $pdo = Database::getConnection();
        $hospitals = $pdo->query("SELECT hospital_id, hospital_name, city FROM hospitals WHERE approval_status = 'APPROVED' ORDER BY hospital_name ASC")->fetchAll();

        $this->render('public/register_hospital', [
            'title' => 'Register Hospital Staff — BloodLink',
            'hospitals' => $hospitals
        ]);
    }

    public function doRegisterHospital(): void {
        $this->validateCsrf();

        $data = $this->request->all();
        $result = AuthService::registerHospitalStaff($data);

        if (!$result['success']) {
            Session::setFlash('danger', $result['message']);
            $this->redirect('/register/hospital');
            return;
        }

        Session::setFlash('success', $result['message']);
        $this->redirect('/login');
    }
}
