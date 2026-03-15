<?php
class CreditosController
{
    private ClientesRepository $clientesRepo;
    private CreditosRepository $creditosRepo;
    private UsuarioRepository $usuarioRepo;
    private CreditosService $creditosService;

    public function __construct()
    {
        $this->clientesRepo = new ClientesRepository();
        $this->creditosRepo = new CreditosRepository();
        $this->usuarioRepo = new UsuarioRepository();
        $this->creditosService = new CreditosService();
    }

    public function index(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: /proyecto-residencia/public/auth/login');
            exit;
        }

        // Solo administradores pueden gestionar créditos
        if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] !== 1 && $_SESSION['usuario_rol'] !== null) {
            header('Location: /proyecto-residencia/public/dashboard');
            exit;
        }

        $clientes = $this->clientesRepo->obtenerTodos();
        $cobratarios = $this->usuarioRepo->obtenerCobratarios();
        $creditos = $this->creditosRepo->obtenerTodos();

        // Obtener configuraciones de tipos de crédito
        $configuraciones = $this->creditosService->obtenerTodasLasConfiguraciones();

        $view = __DIR__ . '/../views/creditos/index.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function guardar(): void
    {
        // Verificar que es una solicitud POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        if (!isset($_SESSION['usuario_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        try {
            // Obtener datos del formulario
            $idcliente = $_POST['idpersona'] ?? null;
            $idcobratario = $_POST['idcobratario'] ?? null;
            $monto = floatval($_POST['monto'] ?? 0);
            $tipo = $_POST['tipo'] ?? 'mensual';
            $interes = floatval($_POST['interes'] ?? 0);
            $moratorio = floatval($_POST['moratorio'] ?? 0);
            $pagos = intval($_POST['pagos'] ?? 0);
            $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');

            // Validaciones
            if (!$idcliente) {
                throw new Exception('Debe seleccionar un cliente');
            }
            if (empty($idcobratario)) {
                throw new Exception('Debe seleccionar un cobratario');
            }
            if ($monto <= 0) {
                throw new Exception('El monto debe ser mayor a 0');
            }
            if ($interes < 0) {
                throw new Exception('El interés no puede ser negativo');
            }
            if ($pagos <= 0) {
                throw new Exception('La cantidad de pagos debe ser mayor a 0');
            }
            if (!in_array($tipo, ['diario', 'semanal', 'mensual'])) {
                throw new Exception('Tipo de crédito inválido');
            }

            // Validar que el cliente existe
            $cliente = $this->clientesRepo->obtenerPorId($idcliente);
            if (!$cliente) {
                throw new Exception('Cliente no encontrado');
            }

            // Validar cobratario (ahora es obligatorio)
            $cobratario = $this->usuarioRepo->obtenerCobratarioPorId($idcobratario);
            if (!$cobratario) {
                throw new Exception('Cobratario no encontrado');
            }

            // Preparar datos para guardar
            $datos = [
                'idcliente' => (int)$idcliente,
                'idcobratario' => (int)$idcobratario,
                'monto' => $monto,
                'tipo' => $tipo,
                'interes' => $interes,
                'moratorio' => $moratorio,
                'pagos' => $pagos,
                'fecha_inicio' => $fechaInicio
            ];

            // Guardar el crédito usando el service
            $resultado = $this->creditosService->guardarCredito($datos);

            // Retornar respuesta JSON
            header('Content-Type: application/json');
            echo json_encode($resultado);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'mensaje' => $e->getMessage()
            ]);
        }
    }

    public function obtener(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        try {
            $idCredito = $_GET['id'] ?? null;

            if (!$idCredito) {
                throw new Exception('ID de crédito requerido');
            }

            $credito = $this->creditosRepo->obtenerCredito($idCredito);
            if (!$credito) {
                throw new Exception('Crédito no encontrado');
            }

            $pagos = $this->creditosRepo->obtenerPagosCredito($idCredito);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'credito' => $credito,
                'pagos' => $pagos
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'mensaje' => $e->getMessage()
            ]);
        }
    }

    public function cobrar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        if (!isset($_SESSION['usuario_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        $rol = isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : null;
        if ($rol !== 3) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'mensaje' => 'Solo el cobratario puede registrar cobros']);
            exit;
        }

        try {
            $idCredito = (int)($_POST['idcredito'] ?? 0);
            $idPago = (int)($_POST['idpago'] ?? 0);
            $montoRecibido = (float)($_POST['monto_recibido'] ?? 0);
            $idCobratario = (int)($_SESSION['idpersona'] ?? 0);
            $idUsuarioSesion = (int)($_SESSION['usuario_id'] ?? 0);

            if ($idCredito <= 0 || $idPago <= 0) {
                throw new Exception('Datos de pago inválidos');
            }

            if ($idCobratario <= 0) {
                throw new Exception('No se encontró el cobratario en sesión');
            }

            if ($idUsuarioSesion <= 0) {
                throw new Exception('No se encontró el usuario en sesión');
            }

            if ($montoRecibido <= 0) {
                throw new Exception('El monto recibido debe ser mayor a 0');
            }

            $resultado = $this->creditosRepo->cobrarPagoCobratario(
                $idPago,
                $idCredito,
                $idCobratario,
                $montoRecibido,
                $idUsuarioSesion
            );

            header('Content-Type: application/json');
            echo json_encode($resultado);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'mensaje' => $e->getMessage(),
            ]);
        }
    }

    public function recibo(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: /proyecto-residencia/public/auth/login');
            exit;
        }

        $rol = isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : null;
        if ($rol !== 3) {
            header('Location: /proyecto-residencia/public/dashboard');
            exit;
        }

        try {
            $idPago = (int)($_GET['idpago'] ?? 0);
            $idCredito = (int)($_GET['idcredito'] ?? 0);

            if ($idPago <= 0 || $idCredito <= 0) {
                throw new Exception('Datos inválidos para generar recibo');
            }

            $datos = $this->creditosRepo->obtenerDatosReciboCobro($idPago, $idCredito);

            if (!$datos['success']) {
                throw new Exception($datos['error'] ?? 'Error al obtener datos del recibo');
            }

            $cobro = $datos['cobro'];
            $view = __DIR__ . '/../views/recibos/cobro.php';
            require $view;
        } catch (Exception $e) {
            echo "Error: " . htmlspecialchars($e->getMessage());
        }
    }
}
