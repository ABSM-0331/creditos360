<?php
class CobratariosService
{
    private CobratariosRepository $repository;

    public function __construct()
    {
        $this->repository = new CobratariosRepository();
    }

    public function obtenerTodos(): array
    {
        return $this->repository->obtenerTodos();
    }

    public function obtenerTodosConEstadisticas(): array
    {
        return $this->repository->obtenerTodosConEstadisticas();
    }

    public function crearCobratario(array $data): void
    {
        $this->repository->crearCobratario($data);
    }
}
