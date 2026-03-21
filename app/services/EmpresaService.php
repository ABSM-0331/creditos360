<?php
class EmpresaService
{
    private EmpresaRepository $repository;

    public function __construct()
    {
        $this->repository = new EmpresaRepository();
    }

    public function obtenerDatos(): array
    {
        return $this->repository->obtenerDatos();
    }

    public function guardarDatos(array $data): void
    {
        $this->repository->guardarDatos($data);
    }
}
