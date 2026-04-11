<?php
class CreditosController
{
    private ClientesRepository $clientesRepo;
    private CreditosRepository $creditosRepo;
    private UsuarioRepository $usuarioRepo;
    private CreditosService $creditosService;
    private TicketPrinterService $ticketPrinterService;
    private EmailService $emailService;

    public function __construct()
    {
        $this->clientesRepo = new ClientesRepository();
        $this->creditosRepo = new CreditosRepository();
        $this->usuarioRepo = new UsuarioRepository();
        $this->creditosService = new CreditosService();
        $this->ticketPrinterService = new TicketPrinterService();
        $this->emailService = new EmailService();
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
        $tiposCredito = $this->creditosService->obtenerTiposCredito(false);

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
            if (!$this->creditosService->validarTipoCreditoExiste($tipo)) {
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

    public function guardarTipo(): void
    {
        $this->validarAdministrador();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Método no permitido';
            header('Location: /proyecto-residencia/public/creditos');
            exit;
        }

        try {
            $tipo = $this->normalizarTipo((string)($_POST['tipo'] ?? ''));
            $cantidadPagos = (int)($_POST['cantidad_pagos'] ?? 0);
            $interesDefault = (float)($_POST['interes_default'] ?? 0);
            $diasIntervalo = (int)($_POST['dias_intervalo'] ?? 1);

            if ($tipo === '') {
                throw new Exception('Debes indicar el tipo de crédito');
            }
            if ($cantidadPagos <= 0) {
                throw new Exception('La cantidad de pagos debe ser mayor a 0');
            }
            if ($interesDefault < 0) {
                throw new Exception('El interés por defecto no puede ser negativo');
            }
            if ($diasIntervalo <= 0) {
                throw new Exception('El intervalo en días debe ser mayor a 0');
            }

            $this->creditosService->crearTipoCredito([
                'tipo' => $tipo,
                'cantidad_pagos' => $cantidadPagos,
                'interes_default' => $interesDefault,
                'dias_intervalo' => $diasIntervalo,
            ]);

            $_SESSION['success'] = 'Tipo de crédito creado correctamente';
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: /proyecto-residencia/public/creditos#tipos-credito');
        exit;
    }

    public function actualizarTipo(): void
    {
        $this->validarAdministrador();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Método no permitido';
            header('Location: /proyecto-residencia/public/creditos');
            exit;
        }

        try {
            $idTipo = (int)($_POST['idtipo'] ?? 0);
            $tipo = $this->normalizarTipo((string)($_POST['tipo'] ?? ''));
            $cantidadPagos = (int)($_POST['cantidad_pagos'] ?? 0);
            $interesDefault = (float)($_POST['interes_default'] ?? 0);
            $diasIntervalo = (int)($_POST['dias_intervalo'] ?? 1);
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($idTipo <= 0) {
                throw new Exception('Tipo de crédito inválido');
            }
            if ($tipo === '') {
                throw new Exception('Debes indicar el tipo de crédito');
            }
            if ($cantidadPagos <= 0) {
                throw new Exception('La cantidad de pagos debe ser mayor a 0');
            }
            if ($interesDefault < 0) {
                throw new Exception('El interés por defecto no puede ser negativo');
            }
            if ($diasIntervalo <= 0) {
                throw new Exception('El intervalo en días debe ser mayor a 0');
            }

            $this->creditosService->actualizarTipoCredito($idTipo, [
                'tipo' => $tipo,
                'cantidad_pagos' => $cantidadPagos,
                'interes_default' => $interesDefault,
                'dias_intervalo' => $diasIntervalo,
                'activo' => $activo,
            ]);

            $_SESSION['success'] = 'Tipo de crédito actualizado correctamente';
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: /proyecto-residencia/public/creditos#tipos-credito');
        exit;
    }

    public function eliminarTipo(): void
    {
        $this->validarAdministrador();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Método no permitido';
            header('Location: /proyecto-residencia/public/creditos');
            exit;
        }

        try {
            $idTipo = (int)($_POST['idtipo'] ?? 0);
            if ($idTipo <= 0) {
                throw new Exception('Tipo de crédito inválido');
            }

            $this->creditosService->eliminarTipoCredito($idTipo);
            $_SESSION['success'] = 'Tipo de crédito eliminado correctamente';
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: /proyecto-residencia/public/creditos#tipos-credito');
        exit;
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

            if (!empty($resultado['success']) && !empty($resultado['historial_ids'])) {
                $resultado['ticket_generado'] = true;
                $resultado['ticket_url'] = '/proyecto-residencia/public/creditos/ver-ticket?historial=' . implode(',', (array)$resultado['historial_ids']) . '&idcredito=' . $idCredito;
            }

            header('Content-Type: application/json');
            echo json_encode($resultado);
        } catch (Throwable $e) {
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
            $html = $this->ticketPrinterService->generarHtmlCobro($cobro, true);

            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
        } catch (Throwable $e) {
            header('Content-Type: text/plain; charset=UTF-8');
            http_response_code(400);
            echo 'Error al generar ticket: ' . $e->getMessage();
        }
    }

    public function verTicket(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: /proyecto-residencia/public/auth/login');
            exit;
        }

        $rol = isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : null;
        $esAdmin = $rol === 1;
        $esCobratario = $rol === 3;
        if (!$esAdmin && !$esCobratario) {
            header('Location: /proyecto-residencia/public/dashboard');
            exit;
        }

        try {
            $idPago = (int)($_GET['idpago'] ?? 0);
            $idCredito = (int)($_GET['idcredito'] ?? 0);
            $historialIds = [];

            $historialRaw = $_GET['historial'] ?? null;
            if (is_array($historialRaw)) {
                $historialIds = array_map('intval', $historialRaw);
            } elseif (is_string($historialRaw) && $historialRaw !== '') {
                $historialIds = array_map('intval', array_filter(explode(',', $historialRaw)));
            }

            if ($idCredito <= 0 || (empty($historialIds) && $idPago <= 0)) {
                throw new Exception('Datos inválidos para obtener el ticket');
            }

            if (!empty($historialIds)) {
                $datosRecibo = $this->creditosRepo->obtenerDatosReciboCobro($historialIds, $idCredito);
            } else {
                $datosRecibo = $this->creditosRepo->obtenerDatosReciboCobroPorPago($idPago, $idCredito);
            }

            if (!$datosRecibo['success']) {
                throw new Exception($datosRecibo['error'] ?? 'No se encontró el registro de cobro');
            }

            $cobro = $datosRecibo['cobro'];
            $html = $this->ticketPrinterService->generarHtmlCobro($cobro, true);

            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
        } catch (Throwable $e) {
            header('Content-Type: text/plain; charset=UTF-8');
            http_response_code(400);
            echo 'Error al generar ticket: ' . $e->getMessage();
        }
    }

    public function enviarTicket(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }

        if (!isset($_SESSION['usuario_id'])) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit;
        }

        $rol = isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : null;
        $esAdmin = $rol === 1;
        $esCobratario = $rol === 3;
        if (!$esAdmin && !$esCobratario) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tiene permisos para enviar tickets']);
            exit;
        }

        try {
            $idPago = (int)($_POST['idpago'] ?? 0);
            $idCredito = (int)($_POST['idcredito'] ?? 0);
            $historialIds = [];

            $historialRaw = $_POST['historial'] ?? null;
            if (is_array($historialRaw)) {
                $historialIds = array_map('intval', $historialRaw);
            } elseif (is_string($historialRaw) && $historialRaw !== '') {
                $historialIds = array_map('intval', array_filter(explode(',', $historialRaw)));
            }

            if ($idCredito <= 0 || (empty($historialIds) && $idPago <= 0)) {
                throw new Exception('Datos inválidos para enviar el ticket');
            }

            if (!empty($historialIds)) {
                $datosRecibo = $this->creditosRepo->obtenerDatosReciboCobro($historialIds, $idCredito);
            } else {
                $datosRecibo = $this->creditosRepo->obtenerDatosReciboCobroPorPago($idPago, $idCredito);
            }

            if (!$datosRecibo['success']) {
                throw new Exception($datosRecibo['error'] ?? 'No se encontró el registro de cobro');
            }

            $cobro = $datosRecibo['cobro'];
            $correoDestino = trim((string)($_POST['correo'] ?? ''));
            if ($correoDestino === '') {
                $correoDestino = trim((string)($cobro['cliente']['email'] ?? ''));
            }

            if (!filter_var($correoDestino, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El cliente no tiene un correo válido para enviar el ticket');
            }

            $html = $this->ticketPrinterService->generarHtmlCobro($cobro, false);
            $asunto = 'Ticket de pago ' . (string)($cobro['numero_recibo'] ?? '');

            $from = 'no-reply@localhost';
            $fromName = 'Sistema de Creditos';
            if (class_exists('EmpresaService')) {
                try {
                    $empresa = (new EmpresaService())->obtenerDatos();
                    $nombreEmpresa = trim((string)($empresa['nombre_empresa'] ?? ''));
                    $correoEmpresa = trim((string)($empresa['correo'] ?? ''));
                    if (filter_var($correoEmpresa, FILTER_VALIDATE_EMAIL)) {
                        $from = $correoEmpresa;
                    }
                    if ($nombreEmpresa !== '') {
                        $fromName = $nombreEmpresa;
                    }
                } catch (Throwable $e) {
                    // mantener from por defecto
                }
            }

            $this->emailService->enviarHtml($correoDestino, $asunto, $html, [
                'from' => $from,
                'from_name' => $fromName,
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'mensaje' => 'Ticket enviado correctamente',
                'correo' => $correoDestino,
            ]);
        } catch (Throwable $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function imprimirTicketTermica(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }

        if (!isset($_SESSION['usuario_id'])) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit;
        }

        $rol = isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : null;
        $esAdmin = $rol === 1;
        $esCobratario = $rol === 3;
        if (!$esAdmin && !$esCobratario) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tiene permisos para imprimir tickets']);
            exit;
        }

        try {
            $idPago = (int)($_POST['idpago'] ?? 0);
            $idCredito = (int)($_POST['idcredito'] ?? 0);
            $historialIds = [];

            $historialRaw = $_POST['historial'] ?? null;
            if (is_array($historialRaw)) {
                $historialIds = array_map('intval', $historialRaw);
            } elseif (is_string($historialRaw) && $historialRaw !== '') {
                $historialIds = array_map('intval', array_filter(explode(',', $historialRaw)));
            }

            if ($idCredito <= 0 || (empty($historialIds) && $idPago <= 0)) {
                throw new Exception('Datos inválidos para imprimir el ticket');
            }

            if (!empty($historialIds)) {
                $datosRecibo = $this->creditosRepo->obtenerDatosReciboCobro($historialIds, $idCredito);
            } else {
                $datosRecibo = $this->creditosRepo->obtenerDatosReciboCobroPorPago($idPago, $idCredito);
            }

            if (!$datosRecibo['success']) {
                throw new Exception($datosRecibo['error'] ?? 'No se encontró el registro de cobro');
            }

            $cobro = $datosRecibo['cobro'];
            $resultadoImpresion = $this->ticketPrinterService->imprimirTicket($cobro);

            if (empty($resultadoImpresion['ok'])) {
                throw new Exception((string)($resultadoImpresion['error'] ?? 'No se pudo imprimir el ticket'));
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'mensaje' => 'Payload de impresión generado correctamente',
                'payload' => $resultadoImpresion['payload'] ?? null,
            ]);
        } catch (Throwable $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function validarAdministrador(): void
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

    private function normalizarTipo(string $tipo): string
    {
        $tipo = strtolower(trim($tipo));
        $tipo = preg_replace('/[^a-z0-9_\-]/', '_', $tipo);
        $tipo = preg_replace('/_+/', '_', (string)$tipo);
        return trim((string)$tipo, '_');
    }
}
