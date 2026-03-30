<?php
class CreditosService
{
    private CreditosRepository $creditosRepo;

    public function __construct()
    {
        $this->creditosRepo = new CreditosRepository();
    }

    /**
     * Obtiene la configuración según el tipo de crédito
     */
    public function obtenerConfiguracionTipo(string $tipo): array
    {
        $configuraciones = $this->obtenerTodasLasConfiguraciones();
        if (isset($configuraciones[$tipo])) {
            return $configuraciones[$tipo];
        }

        return !empty($configuraciones) ? reset($configuraciones) : [
            'pagos' => 1,
            'interes' => 0,
            'moratorio' => 35,
            'modo' => 'fijo',
            'intervalo' => 'P1D',
            'dias_intervalo' => 1,
            'es_flexible' => false,
        ];
    }

    /**
     * Obtiene todas las configuraciones
     */
    public function obtenerTodasLasConfiguraciones(): array
    {
        return $this->creditosRepo->obtenerConfiguracionesTiposCredito();
    }

    /**
     * Guarda un crédito con validación
     */
    public function guardarCredito(array $datos)
    {
        return $this->creditosRepo->guardarCredito($datos);
    }

    public function obtenerTiposCredito(bool $soloActivos = true): array
    {
        return $this->creditosRepo->obtenerTiposCredito($soloActivos);
    }

    public function validarTipoCreditoExiste(string $tipo): bool
    {
        $tipoBuscado = strtolower(trim($tipo));
        $tiposActivos = $this->creditosRepo->obtenerTiposCredito(true);
        foreach ($tiposActivos as $item) {
            if (strtolower((string)$item['tipo']) === $tipoBuscado) {
                return true;
            }
        }

        return false;
    }

    public function crearTipoCredito(array $datos): int
    {
        return $this->creditosRepo->crearTipoCredito($datos);
    }

    public function actualizarTipoCredito(int $idTipo, array $datos): bool
    {
        return $this->creditosRepo->actualizarTipoCredito($idTipo, $datos);
    }

    public function eliminarTipoCredito(int $idTipo): bool
    {
        return $this->creditosRepo->eliminarTipoCredito($idTipo);
    }
}
