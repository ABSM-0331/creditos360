<?php
class UbicacionesController
{
    private UbicacionesRepository $repository;

    public function __construct()
    {
        $this->repository = new UbicacionesRepository();
    }

    public function obtenerEstados(): void
    {
        header('Content-Type: application/json');
        try {
            $estados = $this->repository->obtenerEstados();
            echo json_encode([
                'success' => true,
                'data' => $estados
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener estados: ' . $e->getMessage()
            ]);
        }
    }

    public function obtenerMunicipios(): void
    {
        header('Content-Type: application/json');
        try {
            if (!isset($_GET['idestado'])) {
                throw new Exception('ID de estado no proporcionado');
            }

            $idestado = (int)$_GET['idestado'];
            $municipios = $this->repository->obtenerMunicipiosPorEstado($idestado);

            echo json_encode([
                'success' => true,
                'data' => $municipios
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener municipios: ' . $e->getMessage()
            ]);
        }
    }
}
