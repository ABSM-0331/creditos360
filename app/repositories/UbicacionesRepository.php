<?php
class UbicacionesRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DBC::get();
    }

    public function obtenerEstados(): array
    {
        $sql = "SELECT idestado, nombre FROM estados ORDER BY nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerMunicipiosPorEstado(int $idestado): array
    {
        $sql = "SELECT idmunicipio, nombre FROM municipios WHERE idestado = :idestado ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':idestado', $idestado, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
