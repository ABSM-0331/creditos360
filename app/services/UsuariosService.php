<?php
class UsuariosService
{
    private UsuarioRepository $repository;

    public function __construct()
    {
        $this->repository = new UsuarioRepository();
    }

    public function obtenerTodos(): array
    {
        return $this->repository->obtenerTodos();
    }

    public function obtenerPorId(int $id): ?array
    {
        return $this->repository->obtenerPorId($id);
    }

    public function obtenerPersonasSinUsuario(): array
    {
        return $this->repository->obtenerPersonasSinUsuario();
    }

    public function crearUsuario(array $data): int
    {
        return $this->repository->crearUsuario($data);
    }

    public function actualizarUsuario(int $id, array $data): void
    {
        $this->repository->actualizarUsuario($id, $data);
    }
}
