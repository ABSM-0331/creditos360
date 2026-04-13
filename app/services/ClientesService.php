<?php
class ClientesService
{
    private ClientesRepository $repository;

    public function __construct()
    {
        $this->repository = new ClientesRepository();
    }

    public function obtenerTodos(): array
    {
        return $this->repository->obtenerTodos();
    }

    public function crearCliente(array $data): int
    {
        return $this->repository->crearCliente($data);
    }
}
