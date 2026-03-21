<?php
class EmpresaController
{
    private EmpresaService $service;

    public function __construct()
    {
        $this->service = new EmpresaService();
    }

    private function validarAccesoAdmin(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: /proyecto-residencia/public/auth/login');
            exit;
        }

        if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] !== 1 && $_SESSION['usuario_rol'] !== null) {
            header('Location: /proyecto-residencia/public/dashboard');
            exit;
        }
    }

    public function index(): void
    {
        $this->validarAccesoAdmin();

        $empresa = $this->service->obtenerDatos();
        $view = __DIR__ . '/../views/empresa/index.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function guardar(): void
    {
        $this->validarAccesoAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto-residencia/public/empresa');
            exit;
        }

        try {
            $empresaActual = $this->service->obtenerDatos();
            $logoRuta = $empresaActual['logo_ruta'] ?? null;

            if (!empty($_FILES['empresa_logo']['name'])) {
                $directorio = __DIR__ . '/../../public/uploads/empresa/';
                if (!is_dir($directorio)) {
                    mkdir($directorio, 0777, true);
                }

                $tmp = $_FILES['empresa_logo']['tmp_name'] ?? '';
                if ($tmp === '' || !is_uploaded_file($tmp)) {
                    throw new Exception('No se recibio un archivo de logo valido.');
                }

                $mime = mime_content_type($tmp);
                $permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];

                if (!in_array($mime, $permitidos, true)) {
                    throw new Exception('El logo debe ser una imagen JPG, PNG, WEBP o SVG.');
                }

                if (($_FILES['empresa_logo']['size'] ?? 0) > 2 * 1024 * 1024) {
                    throw new Exception('El logo no debe superar los 2MB.');
                }

                $nombreArchivo = uniqid('empresa_', true) . '_' . basename($_FILES['empresa_logo']['name']);
                $nombreArchivo = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombreArchivo);
                $rutaCompleta = $directorio . $nombreArchivo;

                if (!move_uploaded_file($tmp, $rutaCompleta)) {
                    throw new Exception('No se pudo subir el logo.');
                }

                if (!empty($logoRuta)) {
                    $logoAnterior = __DIR__ . '/../../public/' . ltrim($logoRuta, '/');
                    if (is_file($logoAnterior)) {
                        @unlink($logoAnterior);
                    }
                }

                $logoRuta = 'uploads/empresa/' . $nombreArchivo;
            }

            $data = [
                'nombre_empresa' => trim($_POST['nombre_empresa'] ?? ''),
                'direccion' => trim($_POST['direccion'] ?? ''),
                'correo' => trim($_POST['correo'] ?? ''),
                'representante_legal' => trim($_POST['representante_legal'] ?? ''),
                'rfc' => strtoupper(trim($_POST['rfc'] ?? '')),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'logo_ruta' => $logoRuta,
            ];

            if ($data['nombre_empresa'] === '') {
                throw new Exception('El nombre de la empresa es obligatorio.');
            }

            if ($data['correo'] !== '' && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El correo de la empresa no tiene un formato valido.');
            }

            if ($data['rfc'] !== '' && !preg_match('/^[A-Z&\xD1]{3,4}[0-9]{6}[A-Z0-9]{3}$/', $data['rfc'])) {
                throw new Exception('El RFC no tiene un formato valido.');
            }

            $this->service->guardarDatos($data);
            $_SESSION['success'] = 'Datos de la empresa guardados correctamente.';
            header('Location: /proyecto-residencia/public/empresa');
            exit;
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Error al guardar datos de empresa: ' . $e->getMessage();
            header('Location: /proyecto-residencia/public/empresa');
            exit;
        }
    }
}
