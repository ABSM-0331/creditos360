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

    public function crearCliente(array $data): void
    {
        $this->repository->crearCliente($data);
    }
}
