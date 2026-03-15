<?php
class ClientesController
{
    private ClientesService $service;
    public function __construct()
    {
        $this->service = new ClientesService();
    }
    public function mostrarCatalogoClientes(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('location: /proyecto-residencia/public/login');
            exit;
        }
        $view = __DIR__ . '/../views/clientes/index.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function clientes(): array
    {
        return $this->service->obtenerTodos();
    }

    public function vistaCrearCliente(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('location: /proyecto-residencia/public/login');
            exit;
        }
        $view = __DIR__ . '/../views/clientes/crearCliente.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function crearCliente()
    {
        try {
            // =============================
            // Subir foto
            // =============================
            $fotoRuta = null;

            if (!empty($_FILES['foto_ruta']['name'])) {

                $directorio = __DIR__ . '/../../public/uploads/clientes/';
                if (!is_dir($directorio)) {
                    mkdir($directorio, 0777, true);
                }

                $nombreArchivo = uniqid() . '_' . basename($_FILES['foto_ruta']['name']);
                $rutaCompleta = $directorio . $nombreArchivo;

                if (!move_uploaded_file($_FILES['foto_ruta']['tmp_name'], $rutaCompleta)) {
                    throw new Exception("Error al subir la foto");
                }

                $fotoRuta = 'uploads/clientes/' . $nombreArchivo;
            }

            // =============================
            // Preparar datos
            // =============================
            $data = [
                "ap_paterno" => $_POST["ap_paterno"],
                "ap_materno" => $_POST["ap_materno"] ?? null,
                "nombres" => $_POST["nombres"],
                "sexo" => $_POST["sexo"] ?? null,
                "fecha_nacimiento" => $_POST["fecha_nacimiento"] ?? null,
                "edad" => $_POST["edad"] ?? null,
                "curp" => $_POST["curp"] ?? null,
                "clave_elector" => $_POST["clave_elector"] ?? null,
                "email" => $_POST["email"] ?? null,
                "telefono" => $_POST["telefono"],
                "foto_ruta" => $fotoRuta,

                "dom_calle" => $_POST["dom_calle"] ?? null,
                "dom_numero" => $_POST["dom_numero"] ?? null,
                "dom_colonia" => $_POST["dom_colonia"] ?? null,
                "dom_cruz1" => $_POST["dom_cruz1"] ?? null,
                "dom_cruz2" => $_POST["dom_cruz2"] ?? null,
                "dom_cp" => $_POST["dom_cp"] ?? null,
                "idestado" => $_POST["idestado"] ?? null,
                "idmunicipio" => $_POST["idmunicipio"] ?? null,
                "dom_referencia" => $_POST["dom_referencia"] ?? null,
            ];

            // =============================
            // Llamar al SERVICE
            // =============================
            $clienteId = $this->service->crearCliente($data);
            $_SESSION['success'] = 'Cliente creado correctamente';
            header('location: /proyecto-residencia/public/clientes');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al crear cliente: ' . $e->getMessage();
            header('location: /proyecto-residencia/public/nuevo-cliente');
            exit;
        }
    }
}
