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
        $configuraciones = [
            'diario' => [
                'pagos' => 35,
                'interes' => 22.5,
                'moratorio' => 35,
                'modo' => 'fijo',
                'intervalo' => 'P1D'
            ],
            'semanal' => [
                'pagos' => 12,
                'interes' => 50,
                'moratorio' => 125,
                'modo' => 'fijo',
                'intervalo' => 'P7D'
            ],
            'mensual' => [
                'pagos' => 3,
                'interes' => 50,
                'moratorio' => 800,
                'modo' => 'flexible',
                'intervalo' => 'P1M'
            ]
        ];

        return $configuraciones[$tipo] ?? $configuraciones['mensual'];
    }

    /**
     * Obtiene todas las configuraciones
     */
    public function obtenerTodasLasConfiguraciones(): array
    {
        return [
            'diario' => $this->obtenerConfiguracionTipo('diario'),
            'semanal' => $this->obtenerConfiguracionTipo('semanal'),
            'mensual' => $this->obtenerConfiguracionTipo('mensual')
        ];
    }

    /**
     * Guarda un crédito con validación
     */
    public function guardarCredito(array $datos)
    {
        return $this->creditosRepo->guardarCredito($datos);
    }
}
