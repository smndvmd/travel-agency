<?php
declare(strict_types=1);
namespace Tourism\Controllers;

use Tourism\Core\Controller;
use Tourism\Repositories\UserRepository;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        require_once BASE_PATH . '/config.php';
        if (me()) { $this->redirect(''); }
        $this->render('auth/login', ['title' => 'Воридшавӣ', 'err' => '']);
    }
    public function login(): void
    {
        require_once BASE_PATH . '/config.php';
        csrf_check();
        $user = (new UserRepository())->findByEmail(trim((string)($_POST['email'] ?? '')));
        if (!$user || !password_verify((string)($_POST['password'] ?? ''), $user['password_hash'])) {
            $this->render('auth/login', ['title' => 'Воридшавӣ', 'err' => 'Email ё парол нодуруст аст.'], 422);
            return;
        }
        if (!$user['is_active']) {
            $this->render('auth/login', ['title' => 'Воридшавӣ', 'err' => 'Ҳисоби шумо баста шудааст.'], 422);
            return;
        }
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$user['id'];
        $this->redirect(['admin' => 'admin/index.php', 'manager' => 'manager/index.php'][$user['role']] ?? '');
    }
    public function showRegister(): void
    {
        require_once BASE_PATH . '/config.php';
        if (me()) { $this->redirect(''); }
        $this->render('auth/register', ['title' => 'Сабти ном', 'err' => '']);
    }
    public function register(): void
    {
        require_once BASE_PATH . '/config.php';
        csrf_check();
        $name = trim((string)($_POST['full_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $error = '';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Ном ва email-ро дуруст ворид кунед.';
        elseif (strlen($password) < 6) $error = 'Парол бояд ақаллан 6 аломат бошад.';
        else {
            $users = new UserRepository();
            if ($users->findByEmail($email)) $error = 'Ин email аллакай сабт шудааст.';
            else {
                session_regenerate_id(true);
                $_SESSION['uid'] = $users->create($name, $email, $phone, password_hash($password, PASSWORD_DEFAULT));
                flash('Хуш омадед! Сабти ном анҷом ёфт.');
                $this->redirect('');
            }
        }
        $this->render('auth/register', ['title' => 'Сабти ном', 'err' => $error, 'input' => $_POST], 422);
    }
}
