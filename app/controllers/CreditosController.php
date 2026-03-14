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
            $confirmarCreditoActivo = filter_var($_POST['confirmar_credito_activo'] ?? false, FILTER_VALIDATE_BOOLEAN);

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

            // Validar si el cliente ya tiene un crédito activo no liquidado.
            // Si existe y no se confirmó explícitamente, solicitar confirmación al frontend.
            $creditoActivo = $this->creditosRepo->obtenerCreditoActivoCliente((int)$idcliente);
            if ($creditoActivo && !$confirmarCreditoActivo) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'requiere_confirmacion' => true,
                    'mensaje' => 'El cliente ya tiene un crédito activo sin liquidar. ¿Deseas otorgar otro crédito de todos modos?',
                    'credito_activo' => [
                        'idcredito' => $creditoActivo['idcredito'] ?? null,
                        'saldo_pendiente' => isset($creditoActivo['saldo_pendiente']) ? (float)$creditoActivo['saldo_pendiente'] : null,
                    ]
                ]);
                exit;
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
        $esAdmin = $rol === 1;
        $esCobratario = $rol === 3;
        if (!$esAdmin && !$esCobratario) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'mensaje' => 'Solo el administrador o cobratario puede registrar cobros']);
            exit;
        }

        try {
            $idCredito = (int)($_POST['idcredito'] ?? 0);
            $idPago = (int)($_POST['idpago'] ?? 0);
            $pagosRaw = $_POST['pagos'] ?? null;
            $montoRecibido = (float)($_POST['monto_recibido'] ?? 0);
            $abonoCapital = (float)($_POST['abono_capital'] ?? 0);
            $metodoPago = strtolower(trim((string)($_POST['metodo_pago'] ?? 'efectivo')));
            $confirmarAnticipado = filter_var($_POST['confirmar_anticipado'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $idCobratario = (int)($_SESSION['idpersona'] ?? 0);
            $idUsuarioSesion = (int)($_SESSION['usuario_id'] ?? 0);

            $metodosPermitidos = ['efectivo', 'transferencia', 'tarjeta_debito', 'tarjeta_credito'];
            if (!in_array($metodoPago, $metodosPermitidos, true)) {
                throw new Exception('Método de pago inválido');
            }

            $idPagos = [];
            if (is_array($pagosRaw)) {
                $idPagos = array_map('intval', $pagosRaw);
            } elseif (is_string($pagosRaw) && $pagosRaw !== '') {
                $pagosDecodificados = json_decode($pagosRaw, true);
                if (is_array($pagosDecodificados)) {
                    $idPagos = array_map('intval', $pagosDecodificados);
                }
            }

            if (empty($idPagos) && $idPago > 0) {
                $idPagos = [$idPago];
            }

            if ($idCredito <= 0 || empty($idPagos)) {
                throw new Exception('Datos de pago inválidos');
            }

            if (!$esAdmin && $idCobratario <= 0) {
                throw new Exception('No se encontró el cobratario en sesión');
            }

            if ($idUsuarioSesion <= 0) {
                throw new Exception('No se encontró el usuario en sesión');
            }

            if ($montoRecibido <= 0) {
                throw new Exception('El monto recibido debe ser mayor a 0');
            }

            if ($abonoCapital < 0) {
                throw new Exception('El abono a capital no puede ser negativo');
            }

            $resultado = $this->creditosRepo->cobrarPagosCobratario(
                $idPagos,
                $idCredito,
                $idCobratario,
                $montoRecibido,
                $idUsuarioSesion,
                $confirmarAnticipado,
                $esAdmin,
                $abonoCapital,
                $metodoPago
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
        if ($rol !== 3 && $rol !== 1) {
            header('Location: /proyecto-residencia/public/dashboard');
            exit;
        }

        try {
            $idCredito = (int)($_GET['idcredito'] ?? 0);
            $idPago = (int)($_GET['idpago'] ?? 0);
            $historialIds = [];

            $historialRaw = $_GET['historial'] ?? null;
            if (is_array($historialRaw)) {
                $historialIds = array_map('intval', $historialRaw);
            } elseif (is_string($historialRaw) && $historialRaw !== '') {
                $historialIds = array_map('intval', array_filter(explode(',', $historialRaw)));
            }

            if ($idCredito <= 0 || (empty($historialIds) && $idPago <= 0)) {
                throw new Exception('Datos inválidos para generar recibo');
            }

            if (!empty($historialIds)) {
                $datos = $this->creditosRepo->obtenerDatosReciboCobro($historialIds, $idCredito);
            } else {
                $datos = $this->creditosRepo->obtenerDatosReciboCobroPorPago($idPago, $idCredito);
            }

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
