<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Cobro #<?php echo htmlspecialchars($cobro['numero_recibo']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .recibo-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 40px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .encabezado {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }

        .encabezado h1 {
            font-size: 28px;
            color: #007bff;
            margin-bottom: 5px;
        }

        .encabezado p {
            font-size: 12px;
            color: #666;
        }

        .numero-recibo {
            font-size: 14px;
            font-weight: bold;
            color: #007bff;
            margin-top: 10px;
        }

        .seccion {
            margin-bottom: 25px;
        }

        .seccion-titulo {
            font-size: 13px;
            font-weight: bold;
            color: #007bff;
            text-transform: uppercase;
            padding-bottom: 8px;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 15px;
        }

        .fila-datos {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .fila-datos .etiqueta {
            font-weight: 600;
            color: #333;
            flex: 0 0 40%;
        }

        .fila-datos .valor {
            text-align: right;
            flex: 1;
            color: #666;
        }

        .fila-monto {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 15px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }

        .fila-monto .etiqueta {
            font-weight: 600;
            color: #333;
        }

        .fila-monto .valor {
            font-weight: 600;
            color: #007bff;
        }

        .tabla-pagos {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .tabla-pagos th,
        .tabla-pagos td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .tabla-pagos th {
            background-color: #f3f6fb;
            color: #1f2937;
            font-weight: 700;
        }

        .tabla-pagos td.num,
        .tabla-pagos th.num {
            text-align: right;
        }

        .badge-vencida {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            color: #b91c1c;
            background: #fee2e2;
        }

        .badge-tiempo {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            color: #065f46;
            background: #d1fae5;
        }

        .monto-total {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            padding: 15px;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
            margin: 15px 0;
        }

        .monto-total .etiqueta {
            font-weight: bold;
        }

        .monto-total .valor {
            font-weight: bold;
        }

        .pie-pagina {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #999;
        }

        .firma-espacios {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
            text-align: center;
        }

        .firma-espacios .firma {
            flex: 1;
        }

        .firma-espacios .linea {
            border-top: 1px solid #333;
            width: 150px;
            margin: 0 auto 5px;
        }

        .firma-espacios .nombre {
            font-size: 12px;
            color: #333;
        }

        @media print {
            body {
                background-color: white;
                padding: 0;
            }

            .recibo-container {
                box-shadow: none;
                border: none;
                max-width: 100%;
                margin: 0;
                padding: 20px;
            }

            .pie-pagina {
                display: none;
            }
        }

        .btn-container {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-print {
            background-color: #007bff;
            color: white;
        }

        .btn-print:hover {
            background-color: #0056b3;
        }

        .btn-close {
            background-color: #6c757d;
            color: white;
        }

        .btn-close:hover {
            background-color: #545b62;
        }

        .titulo-sistema {
            font-size: 11px;
            color: #999;
            margin-bottom: 2px;
        }
    </style>
</head>

<body>
    <div class="recibo-container">
        <!-- Encabezado -->
        <div class="encabezado">
            <p class="titulo-sistema">Sistema de Créditos 360</p>
            <h1>RECIBO DE COBRO</h1>
            <p class="numero-recibo"><?php echo htmlspecialchars($cobro['numero_recibo']); ?></p>
        </div>

        <!-- Datos del Cliente -->
        <div class="seccion">
            <div class="seccion-titulo">Datos del Cliente</div>
            <div class="fila-datos">
                <span class="etiqueta">Nombre:</span>
                <span class="valor"><?php echo htmlspecialchars($cobro['cliente']['nombre']); ?></span>
            </div>
            <div class="fila-datos">
                <span class="etiqueta">Cédula:</span>
                <span class="valor"><?php echo htmlspecialchars($cobro['cliente']['cedula']); ?></span>
            </div>
            <div class="fila-datos">
                <span class="etiqueta">Teléfono:</span>
                <span class="valor"><?php echo htmlspecialchars($cobro['cliente']['telefono']); ?></span>
            </div>
            <div class="fila-datos">
                <span class="etiqueta">Ciudad:</span>
                <span class="valor"><?php echo htmlspecialchars($cobro['cliente']['ciudad']); ?></span>
            </div>
        </div>

        <!-- Datos del Crédito -->
        <div class="seccion">
            <div class="seccion-titulo">Datos del Crédito</div>
            <div class="fila-datos">
                <span class="etiqueta">Crédito #:</span>
                <span class="valor"><?php echo htmlspecialchars($cobro['credito']['idcredito']); ?></span>
            </div>
            <div class="fila-datos">
                <span class="etiqueta">Monto Original:</span>
                <span class="valor">$<?php echo number_format($cobro['credito']['monto_original'], 2, '.', ','); ?></span>
            </div>
            <div class="fila-datos">
                <span class="etiqueta">Tipo de Crédito:</span>
                <span class="valor"><?php echo htmlspecialchars($cobro['credito']['tipo']); ?></span>
            </div>
            <div class="fila-datos">
                <span class="etiqueta">Total de Pagos:</span>
                <span class="valor"><?php echo htmlspecialchars($cobro['credito']['pagos_totales']); ?></span>
            </div>
            <div class="fila-datos">
                <span class="etiqueta">Interés:</span>
                <span class="valor"><?php echo htmlspecialchars($cobro['credito']['interes']); ?>%</span>
            </div>
            <div class="fila-datos">
                <span class="etiqueta">Saldo Pendiente:</span>
                <span class="valor">$<?php echo number_format($cobro['credito']['saldo_pendiente'], 2, '.', ','); ?></span>
            </div>
        </div>

        <!-- Datos del Pago -->
        <div class="seccion">
            <div class="seccion-titulo">Detalle de los Pagos</div>
            <div style="overflow-x: auto;">
                <table class="tabla-pagos">
                    <thead>
                        <tr>
                            <th># Pago</th>
                            <th>Fecha Programada</th>
                            <th>Estado</th>
                            <th class="num">Interés</th>
                            <th class="num">Monto Programado</th>
                            <th class="num">Moratorio</th>
                            <th class="num">Monto Cobrado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cobro['pagos'] as $pago): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pago['numero_pago']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($pago['fecha_programada'])); ?></td>
                                <td>
                                    <?php if (!empty($pago['fue_vencida'])): ?>
                                        <span class="badge-vencida">VENCIDA</span>
                                    <?php else: ?>
                                        <span class="badge-tiempo">A TIEMPO</span>
                                    <?php endif; ?>
                                </td>
                                <td class="num">$<?php echo number_format($pago['interes_programado'], 2, '.', ','); ?></td>
                                <td class="num">$<?php echo number_format($pago['monto_programado'], 2, '.', ','); ?></td>
                                <td class="num">$<?php echo number_format($pago['recargo_moratorio'], 2, '.', ','); ?></td>
                                <td class="num">$<?php echo number_format($pago['monto_pagado'], 2, '.', ','); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Resumen de Cobro -->
        <div class="seccion">
            <div class="seccion-titulo">Resumen de Cobro</div>
            <div class="fila-monto">
                <span class="etiqueta">Letras cobradas:</span>
                <span class="valor"><?php echo number_format($cobro['resumen']['cantidad_pagos_cobrados']); ?></span>
            </div>
            <div class="fila-monto">
                <span class="etiqueta">Total programado:</span>
                <span class="valor">$<?php echo number_format($cobro['resumen']['total_programado'], 2, '.', ','); ?></span>
            </div>
            <div class="fila-monto">
                <span class="etiqueta">Total moratorio:</span>
                <span class="valor">$<?php echo number_format($cobro['resumen']['total_moratorio'], 2, '.', ','); ?></span>
            </div>
            <div class="monto-total">
                <span class="etiqueta">Total Cobrado:</span>
                <span class="valor">$<?php echo number_format($cobro['resumen']['total_cobrado'], 2, '.', ','); ?></span>
            </div>
        </div>

        <!-- Datos Adicionales -->
        <div class="seccion">
            <div class="seccion-titulo">Información de Registro</div>
            <div class="fila-datos">
                <span class="etiqueta">Fecha de Cobro:</span>
                <span class="valor"><?php echo date('d/m/Y', strtotime($cobro['fecha'])); ?></span>
            </div>
            <div class="fila-datos">
                <span class="etiqueta">Hora de Cobro:</span>
                <span class="valor"><?php echo date('H:i', strtotime($cobro['fecha'])); ?></span>
            </div>
            <div class="fila-datos">
                <span class="etiqueta">Cobrador:</span>
                <span class="valor"><?php echo htmlspecialchars($cobro['cobrador']); ?></span>
            </div>
            <div class="fila-datos">
                <span class="etiqueta">Método de Pago:</span>
                <span class="valor"><?php echo htmlspecialchars($cobro['metodo_pago']); ?></span>
            </div>
        </div>

        <!-- Espacios para Firmas -->
        <div class="firma-espacios">
            <div class="firma">
                <div class="linea"></div>
                <div class="nombre">Cliente</div>
            </div>
            <div class="firma">
                <div class="linea"></div>
                <div class="nombre">Cobrador</div>
            </div>
        </div>

        <!-- Pie de Página -->
        <div class="pie-pagina">
            <p>Este recibo es comprobante del cobro realizado.</p>
            <p>Conserve este documento para su referencia.</p>
        </div>

        <!-- Botones (solo visibles en pantalla, no en impresión) -->
        <style>
            @media print {
                .btn-container {
                    display: none;
                }
            }
        </style>
        <div class="btn-container" id="botones-accion">
            <button class="btn btn-print" onclick="window.print()">
                📄 Imprimir / Guardar como PDF
            </button>
            <button class="btn btn-close" onclick="window.close()">
                ✕ Cerrar
            </button>
        </div>
    </div>
</body>

</html>