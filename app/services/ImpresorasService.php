<?php
class ImpresorasService
{
    private ImpresorasRepository $repository;

    public function __construct()
    {
        $this->repository = new ImpresorasRepository();
    }

    public function obtenerRegistradas(int $usuarioId): array
    {
        return $this->repository->obtenerTodas($usuarioId);
    }

    public function obtenerActiva(int $usuarioId): ?array
    {
        return $this->repository->obtenerActiva($usuarioId);
    }

    public function agregar(string $nombre, int $usuarioId): int
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new Exception('Debes indicar el nombre de la impresora');
        }

        if (preg_match('/^(COM|LPT)\d+$/i', $nombre) === 1) {
            throw new Exception('No uses puertos COM/LPT. Registra el nombre de la impresora compartida en Windows.');
        }

        if ($this->repository->existePorNombre($nombre, $usuarioId)) {
            throw new Exception('Esa impresora ya existe en el catálogo');
        }

        return $this->repository->agregar($nombre, $usuarioId);
    }

    public function eliminar(int $idImpresora, int $usuarioId): bool
    {
        if ($idImpresora <= 0) {
            throw new Exception('Impresora inválida');
        }

        return $this->repository->eliminar($idImpresora, $usuarioId);
    }

    public function activar(int $idImpresora, int $usuarioId): bool
    {
        if ($idImpresora <= 0) {
            throw new Exception('Impresora inválida');
        }

        return $this->repository->activar($idImpresora, $usuarioId);
    }

    public function obtenerDisponiblesSistema(): array
    {
        $resultados = [];

        $comandos = [
            'powershell -NoProfile -Command "Get-Printer | Select-Object -ExpandProperty Name"',
            'wmic printer get name',
        ];

        foreach ($comandos as $cmd) {
            $output = @shell_exec($cmd);
            if (!is_string($output) || trim($output) === '') {
                continue;
            }

            $lineas = preg_split('/\r\n|\r|\n/', $output) ?: [];
            foreach ($lineas as $linea) {
                $nombre = trim((string)$linea);
                if ($nombre === '' || stripos($nombre, 'name') === 0) {
                    continue;
                }
                $resultados[] = $nombre;
            }

            if (!empty($resultados)) {
                break;
            }
        }

        $resultados = array_values(array_unique($resultados));
        sort($resultados, SORT_NATURAL | SORT_FLAG_CASE);

        return $resultados;
    }
}
