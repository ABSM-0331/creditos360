<?php
class UsuariosController
{
    private UsuariosService $service;

    public function __construct()
    {
        $this->service = new UsuariosService();
    }

    public function index(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: /proyecto-residencia/public/auth/login');
            exit;
        }

        // Solo administradores pueden gestionar usuarios
        if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] !== 1 && $_SESSION['usuario_rol'] !== null) {
            header('Location: /proyecto-residencia/public/dashboard');
            exit;
        }

        $usuarios = $this->service->obtenerTodos();
        $view = __DIR__ . '/../views/usuarios/index.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function vistaCrear(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: /proyecto-residencia/public/auth/login');
            exit;
        }

        // Solo administradores
        if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] !== 1 && $_SESSION['usuario_rol'] !== null) {
            header('Location: /proyecto-residencia/public/dashboard');
            exit;
        }

        $personas = $this->service->obtenerPersonasSinUsuario();
        $view = __DIR__ . '/../views/usuarios/crearUsuario.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function crear(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: /proyecto-residencia/public/auth/login');
            exit;
        }

        // Solo administradores
        if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] !== 1 && $_SESSION['usuario_rol'] !== null) {
            header('Location: /proyecto-residencia/public/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'idpersona' => $_POST['idpersona'] ?? null,
                'username' => $_POST['username'] ?? null,
                'contrasena' => $_POST['contrasena'] ?? null,
                'confirmar_contrasena' => $_POST['confirmar_contrasena'] ?? null
            ];

            // Validar campos
            if (empty($data['idpersona']) || empty($data['username']) || empty($data['contrasena'])) {
                $_SESSION['error'] = 'Todos los campos son requeridos';
                header('Location: /proyecto-residencia/public/nuevo-usuario');
                exit;
            }

            if ($data['contrasena'] !== $data['confirmar_contrasena']) {
                $_SESSION['error'] = 'Las contraseñas no coinciden';
                header('Location: /proyecto-residencia/public/nuevo-usuario');
                exit;
            }

            try {
                $this->service->crearUsuario($data);
                $_SESSION['success'] = 'Usuario creado correctamente';
                header('Location: /proyecto-residencia/public/usuarios');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al crear usuario: ' . $e->getMessage();
                header('Location: /proyecto-residencia/public/nuevo-usuario');
                exit;
            }
        }
    }

    public function vistaEditar(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: /proyecto-residencia/public/auth/login');
            exit;
        }

        // Solo administradores
        if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] !== 1 && $_SESSION['usuario_rol'] !== null) {
            header('Location: /proyecto-residencia/public/dashboard');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /proyecto-residencia/public/usuarios');
            exit;
        }

        $usuario = $this->service->obtenerPorId($id);
        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado';
            header('Location: /proyecto-residencia/public/usuarios');
            exit;
        }

        $view = __DIR__ . '/../views/usuarios/editarUsuario.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function actualizar(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: /proyecto-residencia/public/auth/login');
            exit;
        }

        // Solo administradores
        if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] !== 1 && $_SESSION['usuario_rol'] !== null) {
            header('Location: /proyecto-residencia/public/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['idusuario'] ?? null;

            if (!$id) {
                $_SESSION['error'] = 'Usuario no encontrado';
                header('Location: /proyecto-residencia/public/usuarios');
                exit;
            }

            $data = [
                'username' => $_POST['username'] ?? null,
                'contrasena' => $_POST['contrasena'] ?? '',
                'confirmar_contrasena' => $_POST['confirmar_contrasena'] ?? ''
            ];

            if (empty($data['username'])) {
                $_SESSION['error'] = 'El usuario es requerido';
                header('Location: /proyecto-residencia/public/editar-usuario?id=' . $id);
                exit;
            }

            if (!empty($data['contrasena'])) {
                if ($data['contrasena'] !== $data['confirmar_contrasena']) {
                    $_SESSION['error'] = 'Las contraseñas no coinciden';
                    header('Location: /proyecto-residencia/public/editar-usuario?id=' . $id);
                    exit;
                }
            }

            try {
                $this->service->actualizarUsuario($id, $data);
                $_SESSION['success'] = 'Usuario actualizado correctamente';
                header('Location: /proyecto-residencia/public/usuarios');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al actualizar usuario: ' . $e->getMessage();
                header('Location: /proyecto-residencia/public/editar-usuario?id=' . $id);
                exit;
            }
        }
    }
}
