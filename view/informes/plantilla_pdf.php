<?php require_once __DIR__ . '/../layout/helpers.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            font-size: 12px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .header h2 {
            font-size: 14px;
            font-weight: normal;
            margin-bottom: 10px;
        }
        
        .info-cliente {
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .info-cliente p {
            margin: 3px 0;
        }
        
        .seccion-titulo {
            background-color: #f0f0f0;
            padding: 8px;
            margin-top: 20px;
            margin-bottom: 10px;
            font-weight: bold;
            border-left: 4px solid #000;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table thead {
            background-color: #e0e0e0;
        }
        
        table th, table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        
        table th {
            font-weight: bold;
            font-size: 11px;
        }
        
        table td {
            font-size: 10px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .saldo-final {
            margin-top: 30px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 2px solid #000;
            text-align: right;
            font-size: 14px;
            font-weight: bold;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        
        .no-datos {
            text-align: center;
            padding: 20px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    
    <!-- ENCABEZADO -->
    <div class="header">
        <h1>SISTEMA DE GESTIÓN DE FIADOS Y PAGOS</h1>
        <h2>© MiGarita.</h2>
    </div>
    
    <!-- INFORMACIÓN DEL CLIENTE -->
    <div class="info-cliente">
        <p><strong>CLIENTE:</strong> <?= e(mb_strtoupper($cliente['nombre'] . ' ' . $cliente['apellido'], 'UTF-8')) ?></p>
        <p><strong>CÉDULA:</strong> <?= e($cliente['cedula']) ?></p>
        <p><strong>TELÉFONO:</strong> <?= e($cliente['telefono']) ?></p>
        <p><strong>CORREO:</strong> <?= e($cliente['correo']) ?></p>
        <?php if (isset($tituloInforme)): ?>
        <p><strong>TIPO DE INFORME:</strong> <?= e($tituloInforme) ?></p>
        <?php endif; ?>
        <p><strong>FECHA DE GENERACIÓN:</strong> <?= date('d/m/Y H:i:s') ?></p>
    </div>
    
    <!-- MOVIMIENTOS DE FIADO -->
    <div class="seccion-titulo">MOVIMIENTOS DE FIADO</div>
    
    <?php if (empty($fiados)): ?>
        <p class="no-datos">No hay movimientos de fiado registrados</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">FECHA</th>
                    <th style="width: 10%;">HORA</th>
                    <th style="width: 25%;">USUARIO QUE ATENDIÓ</th>
                    <th style="width: 33%;">DESCRIPCIÓN</th>
                    <th style="width: 20%;" class="text-right">MONTO</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fiados as $f): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($f['fecha'])) ?></td>
                    <td><?= date('h:i A', strtotime($f['fecha'])) ?></td>
                    <td><?= e($f['usuario_nombre'] . ' ' . $f['usuario_apellido']) ?></td>
                    <td><?= e($f['descripcion']) ?></td>
                    <td class="text-right">$ <?= number_format($f['monto'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <!-- MOVIMIENTOS DE PAGO -->
    <div class="seccion-titulo">MOVIMIENTOS DE PAGO</div>
    
    <?php if (empty($pagos)): ?>
        <p class="no-datos">No hay movimientos de pago registrados</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">FECHA</th>
                    <th style="width: 10%;">HORA</th>
                    <th style="width: 22%;">USUARIO QUE RECIBIÓ DINERO</th>
                    <th style="width: 26%;">DESCRIPCIÓN</th>
                    <th style="width: 15%;">MÉTODO</th>
                    <th style="width: 15%;" class="text-right">MONTO</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagos as $p): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($p['fecha'])) ?></td>
                    <td><?= date('h:i A', strtotime($p['fecha'])) ?></td>
                    <td><?= e($p['usuario_nombre'] . ' ' . $p['usuario_apellido']) ?></td>
                    <td><?= e($p['descripcion']) ?></td>
                    <td><?= e(ucfirst($p['metodo_pago'] ?? 'N/A')) ?></td>
                    <td class="text-right">$ <?= number_format($p['monto_mostrar'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <!-- SALDO FINAL -->
    <div class="saldo-final">
        SALDO POR PAGAR DEL CLIENTE <?= e(mb_strtoupper($cliente['nombre'] . ' ' . $cliente['apellido'], 'UTF-8')) ?>: 
        <span style="color: <?= $saldo > 0 ? '#d9534f' : '#5cb85c' ?>;">
            $ <?= number_format($saldo, 0, ',', '.') ?>
        </span>
    </div>
    
    <!-- PIE DE PÁGINA -->
    <div class="footer">
        © MiGarita. Todos los derechos reservados.
    </div>
    
</body>
</html>