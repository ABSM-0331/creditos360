<?php
class DashboardController
{
    private CreditosRepository $creditosRepo;

    public function __construct()
    {
        $this->creditosRepo = new CreditosRepository();
    }

    public function index(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('location: /proyecto-residencia/public/login');
            exit;
        }
        $view = __DIR__ . '/../views/dashboard/index.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function cliente(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('location: /proyecto-residencia/public/login');
            exit;
        }

        $rol = isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : null;
        if ($rol !== 2) {
            header('location: /proyecto-residencia/public/dashboard');
            exit;
        }

        $idCliente = (int)($_SESSION['idpersona'] ?? 0);
        $creditosCliente = [];
        $resumenCliente = [
            'creditos_activos' => 0,
            'saldo_total' => 0.0,
            'pagos_pendientes' => 0,
            'pagos_vencidos' => 0,
            'monto_proximo_pago' => 0.0,
        ];

        if ($idCliente > 0) {
            $todos = $this->creditosRepo->obtenerCreditosCliente($idCliente);
            foreach ($todos as $credito) {
                $estado = strtolower((string)($credito['estado'] ?? ''));
                $saldo = (float)($credito['saldo_pendiente'] ?? 0);
                if ($estado !== 'activo' || $saldo <= 0) {
                    continue;
                }

                $pagos = $this->creditosRepo->obtenerPagosCredito((int)$credito['idcredito']);
                $pendientes = array_values(array_filter($pagos, static function (array $pago): bool {
                    return strtolower((string)($pago['estado'] ?? '')) !== 'pagado';
                }));

                $proximoPago = $pendientes[0] ?? null;
                $cantidadVencidos = 0;
                foreach ($pendientes as $pagoPendiente) {
                    $estadoPago = strtolower((string)($pagoPendiente['estado'] ?? ''));
                    if ($estadoPago === 'vencido' || $estadoPago === 'atrasado') {
                        $cantidadVencidos++;
                    }
                }

                $montoProximo = $proximoPago ? (float)($proximoPago['monto_cobro_actual'] ?? $proximoPago['monto_programado'] ?? 0) : 0.0;
                $moratorioActual = $proximoPago ? (float)($proximoPago['recargo_moratorio'] ?? 0) : 0.0;

                $credito['tipo_label'] = $this->formatearTipo((string)($credito['tipo'] ?? ''));
                $credito['proximo_pago'] = $proximoPago;
                $credito['pagos_pendientes_detalle'] = $pendientes;
                $credito['cantidad_pendientes'] = count($pendientes);
                $credito['cantidad_vencidos'] = $cantidadVencidos;
                $credito['monto_proximo_pago'] = round($montoProximo, 2);
                $credito['moratorio_actual'] = round($moratorioActual, 2);

                $creditosCliente[] = $credito;

                $resumenCliente['creditos_activos']++;
                $resumenCliente['saldo_total'] += $saldo;
                $resumenCliente['pagos_pendientes'] += count($pendientes);
                $resumenCliente['pagos_vencidos'] += $cantidadVencidos;
                $resumenCliente['monto_proximo_pago'] += $montoProximo;
            }
        }

        $resumenCliente['saldo_total'] = round((float)$resumenCliente['saldo_total'], 2);
        $resumenCliente['monto_proximo_pago'] = round((float)$resumenCliente['monto_proximo_pago'], 2);

        $view = __DIR__ . '/../views/dashboard/cliente.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function cobratario(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('location: /proyecto-residencia/public/login');
            exit;
        }

        $rol = isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : null;
        if ($rol !== 1 && $rol !== 3) {
            header('location: /proyecto-residencia/public/dashboard');
            exit;
        }

        // Obtener el ID del cobratario desde la sesión (idpersona)
        $idCobratario = $_SESSION['idpersona'] ?? null;

        // Obtener créditos para cobranza
        $creditos = [];
        if ($rol === 1) {
            $creditos = $this->creditosRepo->obtenerTodos();
        } elseif ($idCobratario) {
            $creditos = $this->creditosRepo->obtenerCreditosCobratario($idCobratario);
        }

        $totalCreditosAsignados = count($creditos);
        $creditosActivos = 0;
        $clientesUnicos = [];
        $totalCobrado = 0.0;
        $saldoPendienteTotal = 0.0;

        if ($rol === 1) {
            foreach ($creditos as $credito) {
                $totalPagar = (float)($credito['total_pagos'] ?? 0);
                $saldoPendiente = (float)($credito['saldo_pendiente'] ?? 0);
                $totalCobrado += max(0, $totalPagar - $saldoPendiente);
            }
        } elseif ($idCobratario) {
            $totalCobrado = $this->creditosRepo->obtenerTotalCobradoCobratario($idCobratario);
        }

        foreach ($creditos as $credito) {
            if (($credito['estado'] ?? '') === 'activo') {
                $creditosActivos++;
            }

            if (isset($credito['idcliente'])) {
                $clientesUnicos[(string)$credito['idcliente']] = true;
            }

            $saldoPendiente = (float)($credito['saldo_pendiente'] ?? 0);
            $saldoPendienteTotal += $saldoPendiente;
        }

        $resumenCobratario = [
            'totalCreditosAsignados' => $totalCreditosAsignados,
            'creditosActivos' => $creditosActivos,
            'clientesAsignados' => count($clientesUnicos),
            'totalCobrado' => $totalCobrado,
            'saldoPendienteTotal' => $saldoPendienteTotal,
        ];

        $view = __DIR__ . '/../views/dashboard/cobratario.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    private function formatearTipo(string $tipo): string
    {
        $normalizado = str_replace(['_', '-'], ' ', strtolower(trim($tipo)));
        return ucwords($normalizado);
    }
}
