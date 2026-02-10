<?php
/**
 * ARCHIVO: actualizar_registro.php
 * 
 * OBJETIVO: Actualizar un registro existente en la base de datos
 * 
 * METODO: PUT
 * PARAMETROS (JSON):
 * - id: ID del registro a actualizar
 * - temperatura: Nuevo valor de temperatura
 * - humedad: Nuevo valor de humedad
 * - gas: Nuevo valor de gas
 * - distancia: Nuevo valor de distancia
 * - alerta: Nuevo estado de alerta
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT');
header('Access-Control-Allow-Headers: Content-Type');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sistemamonitoreo";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexion
if ($conn->connect_error) {
    die(json_encode([
        "status" => "error", 
        "message" => "Conexión fallida: " . $conn->connect_error
    ]));
}

//OBTENER DATOS DEL BODY
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validar que se recibieron los datos necesarios
if (!isset($data['id']) || !isset($data['temperatura']) || !isset($data['humedad']) || 
    !isset($data['gas']) || !isset($data['distancia']) || !isset($data['alerta'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Faltan parametros requeridos"
    ]);
    exit;
}

// Obtener y validar los datos
$id = intval($data['id']);
$temperatura = floatval($data['temperatura']);
$humedad = floatval($data['humedad']);
$gas = intval($data['gas']);
$distancia = floatval($data['distancia']);
$alerta = intval($data['alerta']);

//ACTUALIZAR REGISTRO
$sql = "UPDATE sensor SET temperatura = ?, humedad = ?, gas = ?, distancia = ?, alerta = ? WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ddidii", $temperatura, $humedad, $gas, $distancia, $alerta, $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Registro actualizado correctamente"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "No se encontro el registro o no hubo cambios"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error al actualizar: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>