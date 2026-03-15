<?php
class AuthController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function mostrarLogin(): void
    {
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
        if (isset($_SESSION['usuario_id'])) {
            header('location: /proyecto-residencia/public/dashboard');
            exit;
        }
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void
    {
        $correo = $_POST['correo'] ?? '';
        $password = $_POST['password'] ?? '';
        if ($this->auth->login($correo, $password)) {
            $rol = $_SESSION['usuario_rol'] ?? null;
            if ($rol === 2) {
                header('location: /proyecto-residencia/public/dashboard-cliente');
            } elseif ($rol === 3) {
                header('location: /proyecto-residencia/public/dashboard-cobratario');
            } else {
                header('location: /proyecto-residencia/public/dashboard');
            }
            exit;
        }
        $_SESSION['error'] = 'Credenciales inválidas';
        header('location: /proyecto-residencia/public/login');
        exit;
    }


    public function logout(): void
    {
        $this->auth->logout();
        header('location: /proyecto-residencia/public/login');
        exit;
    }
}
