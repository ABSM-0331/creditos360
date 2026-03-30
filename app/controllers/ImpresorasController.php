<?php
class ImpresorasController
{
    private ImpresorasService $service;

    public function __construct()
    {
        $this->service = new ImpresorasService();
    }

    private function validarAccesoGestionImpresoras(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: /proyecto-residencia/public/auth/login');
            exit;
        }

        $rol = isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : null;
        if ($rol === 2) {
            $_SESSION['error'] = 'El perfil cliente no tiene acceso al módulo de impresoras.';
            header('Location: /proyecto-residencia/public/dashboard-cliente');
            exit;
        }
    }

    public function index(): void
    {
        $this->validarAccesoGestionImpresoras();

        $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

        $impresoras = $this->service->obtenerRegistradas($usuarioId);
        $disponibles = $this->service->obtenerDisponiblesSistema();
        $view = __DIR__ . '/../views/impresoras/index.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function guardar(): void
    {
        $this->validarAccesoGestionImpresoras();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto-residencia/public/impresoras');
            exit;
        }

        try {
            $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
            $nombre = trim((string)($_POST['nombre'] ?? ''));
            $this->service->agregar($nombre, $usuarioId);
            $_SESSION['success'] = 'Impresora agregada correctamente';
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: /proyecto-residencia/public/impresoras');
        exit;
    }

    public function eliminar(): void
    {
        $this->validarAccesoGestionImpresoras();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto-residencia/public/impresoras');
            exit;
        }

        try {
            $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
            $id = (int)($_POST['idimpresora'] ?? 0);
            $this->service->eliminar($id, $usuarioId);
            $_SESSION['success'] = 'Impresora eliminada correctamente';
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: /proyecto-residencia/public/impresoras');
        exit;
    }

    public function activar(): void
    {
        $this->validarAccesoGestionImpresoras();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto-residencia/public/impresoras');
            exit;
        }

        try {
            $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
            $id = (int)($_POST['idimpresora'] ?? 0);
            $this->service->activar($id, $usuarioId);
            $_SESSION['success'] = 'Impresora activa actualizada';
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: /proyecto-residencia/public/impresoras');
        exit;
    }
}
