<?php

require_once __DIR__ . '/conexion.php';

/**
 * Modelo Producto
 *
 * Contiene las reglas de validacion y las operaciones CRUD solicitadas para
 * trabajar con productos mediante consultas preparadas.
 */
class Producto
{
    private DB $db;
    private ?int $id;
    private string $codigo;
    private string $producto;
    private $precio;
    private $cantidad;

    public function __construct(array $datos = [])
    {
        $this->db = new DB();
        $this->id = isset($datos['id']) && $datos['id'] !== '' ? (int) $datos['id'] : null;
        $this->codigo = trim((string) ($datos['codigo'] ?? ''));
        $this->producto = trim((string) ($datos['producto'] ?? ''));
        $this->precio = $datos['precio'] ?? '';
        $this->cantidad = $datos['cantidad'] ?? '';
    }

    /**
     * Valida los campos obligatorios. En modo guardar la cantidad minima es 1;
     * al modificar se permite 0 para productos agotados.
     */
    public function validar(string $accion = 'Guardar'): array
    {
        $errors = [];

        if ($this->codigo === '') {
            $errors['codigo'] = 'El codigo es obligatorio.';
        }

        if ($this->producto === '') {
            $errors['producto'] = 'El nombre del producto es obligatorio.';
        }

        if ($this->precio === '' || !is_numeric($this->precio)) {
            $errors['precio'] = 'El precio es obligatorio y debe ser numerico.';
        } elseif ((float) $this->precio < 0) {
            $errors['precio'] = 'El precio no debe ser negativo.';
        }

        if ($this->cantidad === '' || !is_numeric($this->cantidad)) {
            $errors['cantidad'] = 'La cantidad es obligatoria y debe ser numerica.';
        } elseif ((int) $this->cantidad < 0) {
            $errors['cantidad'] = 'La cantidad no debe ser negativa.';
        } elseif ($accion === 'Guardar' && (int) $this->cantidad < 1) {
            $errors['cantidad'] = 'Para registrar, la cantidad minima es 1.';
        }

        if ($accion === 'Modificar' && empty($this->id)) {
            $errors['id'] = 'Debe buscar o seleccionar un producto antes de modificar.';
        }

        return $errors;
    }

    public function guardar(): array
    {
        $errors = $this->validar('Guardar');

        if (!empty($errors)) {
            return $this->respuesta(false, 'No se pudo guardar el producto.', 'Guardar', $errors);
        }

        if ($this->existeCodigo($this->codigo)) {
            return $this->respuesta(false, 'Ya existe un producto con ese codigo.', 'Guardar', [
                'codigo' => 'El codigo debe ser unico.',
            ]);
        }

        $sql = 'INSERT INTO productos (codigo, producto, precio, cantidad)
                VALUES (:codigo, :producto, :precio, :cantidad)';

        $id = $this->db->insertSeguro($sql, [
            ':codigo' => $this->codigo,
            ':producto' => $this->producto,
            ':precio' => number_format((float) $this->precio, 2, '.', ''),
            ':cantidad' => (int) $this->cantidad,
        ]);

        return $this->respuesta(true, 'Producto guardado correctamente.', 'Guardar', [], [
            'producto' => $this->buscarPorId($id),
        ]);
    }

    public function editar(): array
    {
        $errors = $this->validar('Modificar');

        if (!empty($errors)) {
            return $this->respuesta(false, 'No se pudo modificar el producto.', 'Modificar', $errors);
        }

        if (!$this->buscarPorId((int) $this->id)) {
            return $this->respuesta(false, 'El producto indicado no existe.', 'Modificar', [
                'id' => 'Producto no encontrado.',
            ]);
        }

        if ($this->existeCodigo($this->codigo, (int) $this->id)) {
            return $this->respuesta(false, 'Ya existe otro producto con ese codigo.', 'Modificar', [
                'codigo' => 'El codigo pertenece a otro producto.',
            ]);
        }

        $sql = 'UPDATE productos
                SET codigo = :codigo, producto = :producto, precio = :precio, cantidad = :cantidad
                WHERE id = :id';

        $this->db->updateSeguro($sql, [
            ':codigo' => $this->codigo,
            ':producto' => $this->producto,
            ':precio' => number_format((float) $this->precio, 2, '.', ''),
            ':cantidad' => (int) $this->cantidad,
            ':id' => (int) $this->id,
        ]);

        return $this->respuesta(true, 'Producto modificado correctamente.', 'Modificar', [], [
            'producto' => $this->buscarPorId((int) $this->id),
        ]);
    }

    public function buscar(?string $termino = null): array
    {
        $termino = trim((string) ($termino ?? $this->codigo));

        if ($termino === '') {
            return $this->respuesta(false, 'Ingrese un codigo, id o nombre para buscar.', 'Buscar', [
                'busqueda' => 'El dato de busqueda es obligatorio.',
            ]);
        }

        if (ctype_digit($termino)) {
            $productos = $this->db->selectSeguro(
                'SELECT id, codigo, producto, precio, cantidad FROM productos WHERE id = :id OR codigo = :codigo LIMIT 1',
                [':id' => (int) $termino, ':codigo' => $termino]
            );
        } else {
            $productos = $this->db->selectSeguro(
                'SELECT id, codigo, producto, precio, cantidad FROM productos
                 WHERE codigo = :codigo OR producto LIKE :producto
                 ORDER BY id DESC',
                [':codigo' => $termino, ':producto' => '%' . $termino . '%']
            );
        }

        if (empty($productos)) {
            return $this->respuesta(false, 'No se encontraron productos.', 'Buscar', [
                'busqueda' => 'Sin coincidencias.',
            ], ['productos' => []]);
        }

        return $this->respuesta(true, 'Producto encontrado correctamente.', 'Buscar', [], [
            'producto' => $productos[0],
            'productos' => $productos,
        ]);
    }

    public function listar(): array
    {
        $productos = $this->db->selectSeguro(
            'SELECT id, codigo, producto, precio, cantidad FROM productos ORDER BY id DESC'
        );

        return $this->respuesta(true, 'Listado de productos cargado correctamente.', 'Listar', [], [
            'productos' => $productos,
        ]);
    }

    public function existeCodigo(string $codigo, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT id FROM productos WHERE codigo = :codigo';
        $params = [':codigo' => $codigo];

        if ($ignorarId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignorarId;
        }

        return !empty($this->db->selectSeguro($sql, $params));
    }

    private function buscarPorId(int $id): ?array
    {
        $productos = $this->db->selectSeguro(
            'SELECT id, codigo, producto, precio, cantidad FROM productos WHERE id = :id LIMIT 1',
            [':id' => $id]
        );

        return $productos[0] ?? null;
    }

    private function respuesta(bool $success, string $message, string $accion, array $errors = [], array $extra = []): array
    {
        return array_merge([
            'success' => $success,
            'message' => $message,
            'accion' => $accion,
            'errors' => $errors,
        ], $extra);
    }
}
