<?php
session_start();
require './fpdf/fpdf.php';
include '../library/configServer.php';
include '../library/consulSQL.php';

// Validar y sanitizar ID
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$id) {
    die('ID de pedido inválido');
}

// Consultar venta
$sVenta = ejecutarSQL::consultar("SELECT * FROM venta WHERE NumPedido='$id'");
if (!$sVenta || mysqli_num_rows($sVenta) == 0) {
    die('Pedido no encontrado');
}
$dVenta = mysqli_fetch_array($sVenta, MYSQLI_ASSOC);

// Consultar cliente
$sCliente = ejecutarSQL::consultar("SELECT * FROM cliente WHERE NIT='".$dVenta['NIT']."'");
if (!$sCliente || mysqli_num_rows($sCliente) == 0) {
    die('Cliente no encontrado');
}
$dCliente = mysqli_fetch_array($sCliente, MYSQLI_ASSOC);

// Limpiar buffer de salida
if (ob_get_length()) ob_end_clean();

// Crear PDF
$pdf = new FPDF('P', 'mm', 'Letter');
$pdf->AddPage();
$pdf->SetMargins(25, 20, 25);
$pdf->SetFont("Times", "", 20);
$pdf->SetFillColor(0, 255, 255);
$pdf->Cell(0, 5, utf8_decode('STORE'), 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont("Times", "", 14);
$pdf->Cell(0, 5, utf8_decode('Factura de pedido número ' . $id), 0, 1, 'C');
$pdf->Ln(20);

$pdf->SetFont("Times", "B", 12);
$pdf->Cell(33, 5, utf8_decode('Fecha del pedido: '), 0);
$pdf->SetFont("Times", "", 12);
$pdf->Cell(37, 5, utf8_decode($dVenta['Fecha']), 0);
$pdf->Ln(12);

$pdf->SetFont("Times", "B", 12);
$pdf->Cell(37, 5, utf8_decode('Nombre del cliente: '), 0);
$pdf->SetFont("Times", "", 12);
$pdf->Cell(100, 5, utf8_decode($dCliente['NombreCompleto'] . " " . $dCliente['Apellido']), 0);
$pdf->Ln(12);

$pdf->SetFont("Times", "B", 12);
$pdf->Cell(30, 5, utf8_decode('DNI/CÉDULA: '), 0);
$pdf->SetFont("Times", "", 12);
$pdf->Cell(25, 5, utf8_decode($dCliente['NIT']), 0);
$pdf->Ln(12);

$pdf->SetFont("Times", "B", 12);
$pdf->Cell(20, 5, utf8_decode('Dirección: '), 0);
$pdf->SetFont("Times", "", 12);
$pdf->Cell(70, 5, utf8_decode($dCliente['Direccion']), 0);
$pdf->Ln(12);

$pdf->SetFont("Times", "B", 12);
$pdf->Cell(19, 5, utf8_decode('Teléfono: '), 0);
$pdf->SetFont("Times", "", 12);
$pdf->Cell(70, 5, utf8_decode($dCliente['Telefono']), 0);

$pdf->SetFont("Times", "B", 12);
$pdf->Cell(14, 5, utf8_decode('Email: '), 0);
$pdf->SetFont("Times", "", 12);
$pdf->Cell(40, 5, utf8_decode($dCliente['Email']), 0);
$pdf->Ln(10);

// Encabezados de tabla
$pdf->SetFont("Times", "B", 12);
$pdf->Cell(76, 10, utf8_decode('Nombre'), 1, 0, 'C');
$pdf->Cell(30, 10, utf8_decode('Precio'), 1, 0, 'C');
$pdf->Cell(30, 10, utf8_decode('Cantidad'), 1, 0, 'C');
$pdf->Cell(30, 10, utf8_decode('Subtotal'), 1, 0, 'C');
$pdf->Ln(10);

// Detalles del pedido
$pdf->SetFont("Times", "", 12);
$suma = 0;
$sDet = ejecutarSQL::consultar("SELECT * FROM detalle WHERE NumPedido='$id'");
while ($fila1 = mysqli_fetch_array($sDet, MYSQLI_ASSOC)) {
    $consulta = ejecutarSQL::consultar("SELECT * FROM producto WHERE CodigoProd='" . $fila1['CodigoProd'] . "'");
    $fila = mysqli_fetch_array($consulta, MYSQLI_ASSOC);
    
    $subtotal = $fila1['PrecioProd'] * $fila1['CantidadProductos'];
    $suma += $subtotal;

    $pdf->Cell(76, 10, utf8_decode($fila['NombreProd']), 1, 0, 'C');
    $pdf->Cell(30, 10, '$' . number_format($fila1['PrecioProd'], 2), 1, 0, 'C');
    $pdf->Cell(30, 10, $fila1['CantidadProductos'], 1, 0, 'C');
    $pdf->Cell(30, 10, '$' . number_format($subtotal, 2), 1, 0, 'C');
    $pdf->Ln(10);

    mysqli_free_result($consulta);
}

// Total
$pdf->SetFont("Times", "B", 12);
$pdf->Cell(76, 10, '', 1, 0, 'C');
$pdf->Cell(30, 10, '', 1, 0, 'C');
$pdf->Cell(30, 10, '', 1, 0, 'C');
$pdf->Cell(30, 10, '$' . number_format($suma, 2, '.', ','), 1, 0, 'C');
$pdf->Ln(10);

// Salida del PDF
$pdf->Output('I', 'Factura-#' . $id . '.pdf');

// Liberar recursos
mysqli_free_result($sVenta);
mysqli_free_result($sCliente);
mysqli_free_result($sDet);
?>