<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/Modelo/conexion.php';
require_once __DIR__ . '/Modelo/Productos.php';

function responder(bool $success, string $message, string $accion = '', array $errors = [], array $extra = []): void
{
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
        'accion' => $accion,
        'errors' => $errors,
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $accion = trim((string) ($_POST['Accion'] ?? ''));

    if ($accion === '') {
        responder(false, 'No se recibio ninguna accion.', '', [
            'Accion' => 'La accion es obligatoria.',
        ]);
    }

    $producto = new Producto($_POST);

    switch ($accion) {
        case 'Guardar':
            $respuesta = $producto->guardar();
            break;

        case 'Modificar':
            $respuesta = $producto->editar();
            break;

        case 'Buscar':
            $respuesta = $producto->buscar($_POST['busqueda'] ?? $_POST['codigo'] ?? '');
            break;

        case 'Listar':
            $respuesta = $producto->listar();
            break;

        default:
            responder(false, 'La accion solicitada no es valida.', $accion, [
                'Accion' => 'Use Guardar, Modificar, Buscar o Listar.',
            ]);
    }

    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $errors = [];

    if (filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN)) {
        $errors['server'] = $e->getMessage();
    }

    responder(false, 'Ocurrio un error al procesar la solicitud.', $_POST['Accion'] ?? '', $errors);
}
