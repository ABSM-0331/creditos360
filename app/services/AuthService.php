<?php
class AuthService
{

    private UsuarioRepository $usuarios;
    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
    }

    public function login(string $correo, string $password): bool
    {
        $usuario = $this->usuarios->buscarPorCorreo($correo);
        if (!$usuario) {
            return false;
        }
        if (!password_verify($password, $usuario['password'])) {
            return false;
        }
        $_SESSION['usuario_id'] = $usuario['idusuario'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_rol'] = $usuario['idrol'];
        $_SESSION['idpersona'] = $usuario['idpersona'];
        return true;
    }


    public function logout(): void
    {
        session_unset();
        session_destroy();
    }
    public function check(): bool
    {
        return isset($_SESSION['usuario_id']);
    }
}
