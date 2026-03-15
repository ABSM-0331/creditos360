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
        $view = __DIR__ . '/../views/dashboard/cliente.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function cobratario(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('location: /proyecto-residencia/public/login');
            exit;
        }

        // Obtener el ID del cobratario desde la sesión (idpersona)
        $idCobratario = $_SESSION['idpersona'] ?? null;

        // Obtener créditos asignados al cobratario
        $creditos = [];
        if ($idCobratario) {
            $creditos = $this->creditosRepo->obtenerCreditosCobratario($idCobratario);
        }

        $totalCreditosAsignados = count($creditos);
        $creditosActivos = 0;
        $clientesUnicos = [];
        $totalCobrado = 0.0;
        $saldoPendienteTotal = 0.0;

        if ($idCobratario) {
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
}
