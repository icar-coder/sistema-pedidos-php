<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

require_once '../includes/db_connection.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario - Sistema de Pedidos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .inventario-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
			margin-left:100px;
        }

        .header-actions {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }

        .stat-card.warning {
            border-left-color: #ffc107;
        }

        .stat-card.danger {
            border-left-color: #dc3545;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-box {
            display: flex;
            gap: 10px;
        }

        .search-box input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 250px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }
.form-row {
    display: flex;
    gap: 10px;
    align-items: end;
    flex-wrap: wrap;
}

.form-row .form-group {
    flex: 1;
    min-width: 120px;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}
        .btn-warning {
            background: #ffc107;
            color: black;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .stock-low {
            color: #dc3545;
            font-weight: bold;
        }

        .stock-ok {
            color: #28a745;
        }

        .actions {
            display: flex;
            gap: 5px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .tab-container {
            margin-bottom: 20px;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            margin-bottom: -1px;
        }

        .tab.active {
            background: white;
            border-color: #ddd;
            border-radius: 4px 4px 0 0;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="admin-container">
        <div class="inventario-container">
            <div class="header-actions">
                <h1>Gestión de Inventario</h1>
                <div>
                    <button class="btn btn-primary" onclick="openModal('ingrediente')">
                        <i class="fas fa-plus"></i> Nuevo Ingrediente
                    </button>
                    <button class="btn btn-success" onclick="openModal('movimiento')">
                        <i class="fas fa-exchange-alt"></i> Movimiento de Inventario
                    </button>
                </div>
            </div>

            <!-- Estadísticas Rápidas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value" id="totalIngredientes">0</div>
                    <div class="stat-label">Total Ingredientes</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value" id="stockBajo">0</div>
                    <div class="stat-label">Stock Bajo</div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-value" id="stockAgotado">0</div>
                    <div class="stat-label">Stock Agotado</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="valorInventario">$0</div>
                    <div class="stat-label">Valor del Inventario</div>
                </div>
            </div>

            <!-- Tabs para diferentes vistas -->
            <div class="tab-container">
                <div class="tabs">
                    <div class="tab active" onclick="switchTab('ingredientes')">Ingredientes</div>
                    <div class="tab" onclick="switchTab('movimientos')">Movimientos</div>
                    <div class="tab" onclick="switchTab('productos')">Recetas</div>
                </div>

                <!-- Tab Ingredientes -->
                <div id="tab-ingredientes" class="tab-content active">
                    <div class="table-header">
                        <div class="search-box">
                            <input type="text" id="searchIngredientes" placeholder="Buscar ingredientes..." onkeyup="filtrarIngredientes()">
                            <select id="filterStock" onchange="filtrarIngredientes()">
                                <option value="">Todos los stocks</option>
                                <option value="bajo">Stock Bajo</option>
                                <option value="agotado">Stock Agotado</option>
                                <option value="normal">Stock Normal</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-container">
                        <table id="tablaIngredientes">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Stock Actual</th>
                                    <th>Stock Mínimo</th>
                                    <th>Unidad</th>
                                    <th>Costo USD</th>
                                    <th>Costo Bs</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyIngredientes">
                                <!-- Los datos se cargan via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Movimientos -->
                <div id="tab-movimientos" class="tab-content">
                    <div class="table-header">
                        <div class="search-box">
                            <input type="date" id="fechaDesde">
                            <input type="date" id="fechaHasta">
                            <button class="btn btn-primary" onclick="cargarMovimientos()">Filtrar</button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table id="tablaMovimientos">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Ingrediente</th>
                                    <th>Tipo</th>
                                    <th>Cantidad</th>
                                    <th>Motivo</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyMovimientos">
                                <!-- Los datos se cargan via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Recetas -->
<div id="tab-productos" class="tab-content">
    <div class="table-header">
        <div class="search-box">
            <input type="text" id="searchProductos" placeholder="Buscar productos..." onkeyup="filtrarProductos()">
            <select id="filterCategoria" onchange="filtrarProductos()">
                <option value="">Todas las categorías</option>
                <!-- Las categorías se cargarán via JavaScript -->
            </select>
        </div>
        <button class="btn btn-primary" onclick="openModal('producto')">
            <i class="fas fa-plus"></i> Nuevo Producto
        </button>
    </div>

    <div class="table-container">
        <table id="tablaProductos">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Categoría</th>
                    <th>Disponible</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyProductos">
                <!-- Los datos se cargan via JavaScript -->
            </tbody>
        </table>
    </div>
</div>
            </div>
        </div>
    </div>

    <!-- Modal para Ingredientes -->
    <div id="modalIngrediente" class="modal">
        <div class="modal-content">
            <h3 id="modalIngredienteTitle">Nuevo Ingrediente</h3>
            <form id="formIngrediente">
                <input type="hidden" id="ingrediente_id" name="ingrediente_id">
                
                <div class="form-group">
                    <label for="nombre_ingrediente">Nombre del Ingrediente *</label>
                    <input type="text" id="nombre_ingrediente" name="nombre_ingrediente" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="costo_dolares">Costo en Dólares *</label>
                    <input type="number" id="costo_dolares" name="costo_dolares" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="stock_actual">Stock Actual *</label>
                    <input type="number" id="stock_actual" name="stock_actual" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="stock_minimo">Stock Mínimo *</label>
                    <input type="number" id="stock_minimo" name="stock_minimo" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="unidad_medida">Unidad de Medida *</label>
                    <select id="unidad_medida" name="unidad_medida" required>
                        <option value="">Seleccionar unidad...</option>
                        <option value="kg">Kilogramos (kg)</option>
                        <option value="g">Gramos (g)</option>
                        <option value="lb">Libras (lb)</option>
                        <option value="lt">Litros (lt)</option>
                        <option value="ml">Mililitros (ml)</option>
                        <option value="unidad">Unidades</option>
                        <option value="paquete">Paquetes</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeModal('ingrediente')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
	
<!-- Modal para Productos con Receta Integrada y Cálculo de Precios -->
<div id="modalProducto" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <h3 id="modalProductoTitle">Nuevo Producto</h3>
        <form id="formProducto">
            <input type="hidden" id="producto_id" name="producto_id">
            
            <!-- Información Básica del Producto -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div class="form-group">
                    <label for="nombre_producto">Nombre del Producto *</label>
                    <input type="text" id="nombre_producto" name="nombre_producto" required>
                </div>

                <div class="form-group">
                    <label for="categoria_producto">Categoría *</label>
                    <select id="categoria_producto" name="id_categoria" required>
                        <option value="">Seleccionar categoría...</option>
                    </select>
                </div>
            </div>

            <!-- Precios -->
            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <h5 style="margin-top: 0; margin-bottom: 15px;">Precios</h5>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="precio_dolares">Precio en Dólares *</label>
                        <input type="number" id="precio_dolares" name="precio_dolares" step="0.01" min="0" required 
                               onchange="calcularPrecioBolivares()">
                        <small style="color: #666;">Precio de venta en USD</small>
                    </div>

                    <div class="form-group">
                        <label for="precio_bolivares">Precio en Bolívares</label>
                        <input type="number" id="precio_bolivares" name="precio_bolivares" step="0.01" min="0" readonly 
                               style="background: #e9ecef;">
                        <small style="color: #666;">Calculado automáticamente</small>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; font-size: 12px; color: #666;">
                    <span>Tasa de cambio: <span id="tasaCambioActual">$1.00 = Bs 0.00</span></span>
                    <button type="button" class="btn btn-sm" onclick="actualizarTasaCambio()" style="padding: 2px 8px;">
                        <i class="fas fa-sync-alt"></i> Actualizar Tasa
                    </button>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div class="form-group">
                    <label for="descripcion_producto">Descripción</label>
                    <textarea id="descripcion_producto" name="descripcion_producto" rows="2"></textarea>
                </div>

                <div class="form-group" style="display: flex; align-items: center; margin-top: 25px;">
                    <label for="disponible_producto" style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="disponible_producto" name="disponible" value="1" checked>
                        Producto disponible
                    </label>
                </div>
            </div>

            <hr style="margin: 20px 0;">

            <!-- Sección de Receta -->
            <h4 style="margin-bottom: 15px;">Receta del Producto</h4>
            
            <!-- Formulario para agregar ingredientes a la receta -->
            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 15px;">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; align-items: end;">
                    <div class="form-group">
                        <label>Ingrediente</label>
                        <select id="selectIngredienteReceta">
                            <option value="">Seleccionar ingrediente...</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Cantidad</label>
                        <input type="number" id="cantidadReceta" step="0.01" min="0.01" placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label>Unidad</label>
                        <input type="text" id="unidadReceta" readonly style="background: #e9ecef;">
                    </div>
                    
                    <div>
                        <button type="button" class="btn btn-success" onclick="agregarIngredienteReceta()" style="margin-bottom: 0;">
                            <i class="fas fa-plus"></i> Agregar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lista de ingredientes en la receta -->
            <div class="table-container" style="max-height: 200px; overflow-y: auto; margin-bottom: 20px;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Ingrediente</th>
                            <th>Cantidad</th>
                            <th>Unidad</th>
                            <th>Costo Unit. (USD)</th>
                            <th>Costo Total (USD)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyRecetaProducto">
                        <!-- Los ingredientes se agregarán aquí -->
                    </tbody>
                </table>
                <div id="mensajeRecetaVacia" style="text-align: center; padding: 20px; color: #666;">
                    No hay ingredientes en la receta. Agrega los ingredientes que componen este producto.
                </div>
            </div>

            <!-- Resumen de costos y márgenes -->
            <div style="background: #e7f3ff; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <h5 style="margin-top: 0; margin-bottom: 10px;">Resumen de Costos y Margen</h5>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <strong>Costo Total de la Receta:</strong><br>
                        <span id="costoTotalRecetaUSD">$0.00 USD</span><br>
                        <span id="costoTotalRecetaBS" style="font-size: 12px; color: #666;">Bs 0.00</span>
                    </div>
                    <div>
                        <strong>Margen de Ganancia:</strong><br>
                        <span id="margenGananciaUSD">$0.00 USD</span> (<span id="porcentajeMargen">0%</span>)<br>
                        <span id="margenGananciaBS" style="font-size: 12px; color: #666;">Bs 0.00</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn" onclick="closeModal('producto')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Producto y Receta</button>
            </div>
        </form>
    </div>
</div>

        <!-- Lista de ingredientes actuales en la receta -->
        <div id="listaRecetaActual" style="margin-top: 20px; display: none;">
            <h4>Ingredientes en la Receta</h4>
            <div id="ingredientesRecetaLista" class="table-container" style="max-height: 200px; overflow-y: auto;">
                <!-- Los ingredientes se cargan aquí -->
            </div>
        </div>
    </div>
</div>
    <!-- Modal para Movimientos -->
    <div id="modalMovimiento" class="modal">
        <div class="modal-content">
            <h3>Movimiento de Inventario</h3>
            <form id="formMovimiento">
                <div class="form-group">
                    <label for="movimiento_ingrediente">Ingrediente *</label>
                    <select id="movimiento_ingrediente" name="ingrediente_id" required>
                        <option value="">Seleccionar ingrediente...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tipo_movimiento">Tipo de Movimiento *</label>
                    <select id="tipo_movimiento" name="tipo_movimiento" required>
                        <option value="entrada">Entrada (Compra)</option>
                        <option value="salida">Salida (Uso)</option>
                        <option value="ajuste">Ajuste</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="cantidad_movimiento">Cantidad *</label>
                    <input type="number" id="cantidad_movimiento" name="cantidad" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="motivo_movimiento">Motivo *</label>
                    <input type="text" id="motivo_movimiento" name="motivo" required>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeModal('movimiento')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar Movimiento</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/admin_inventario.js"></script>
</body>
</html>