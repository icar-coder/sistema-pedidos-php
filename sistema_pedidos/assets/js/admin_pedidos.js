// sistema_pedidos/assets/js/admin_pedidos.js

document.addEventListener('DOMContentLoaded', () => {
    const listarPedidosBtn = document.getElementById('listarPedidosBtn');
    const pedidosTableContainer = document.getElementById('pedidosTableContainer');

    listarPedidosBtn.addEventListener('click', () => {
        fetchPedidos();
    });

    async function fetchPedidos() {
        pedidosTableContainer.innerHTML = '<p>Cargando pedidos...</p>';
        try {
            const response = await fetch('../api/pedidos_admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'read' })
            });
            const result = await response.json();
            
            if (result.success) {
                renderPedidosTable(result.data);
            } else {
                pedidosTableContainer.innerHTML = `<p class="alert alert-danger">Error: ${result.message}</p>`;
            }
        } catch (error) {
            pedidosTableContainer.innerHTML = `<p class="alert alert-danger">Error de conexión o del servidor.</p>`;
            console.error('Error fetching pedidos:', error);
        }
    }

    function renderPedidosTable(pedidos) {
        if (pedidos.length === 0) {
            pedidosTableContainer.innerHTML = '<p class="alert alert-info">No hay pedidos registrados.</p>';
            return;
        }

        const table = document.createElement('table');
        table.className = 'pedidos-table';
        table.innerHTML = `
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Tipo de Servicio</th>
                    <th>Productos</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        `;
        const tbody = table.querySelector('tbody');

        pedidos.forEach(pedido => {
            const productosHtml = pedido.productos.map(prod => `<li>${prod.nombre_producto} (x${prod.cantidad})</li>`).join('');
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${pedido.id_pedido}</td>
                <td>${pedido.nombre_cliente} ${pedido.apellido_cliente}</td>
                <td>${new Date(pedido.fecha_pedido).toLocaleString()}</td>
                <td>${pedido.tipo_servicio}</td>
                <td><ul>${productosHtml}</ul></td>
                <td>$${parseFloat(pedido.total_pedido).toFixed(2)}</td>
                <td><span class="status ${pedido.estado}">${pedido.estado}</span></td>
                <td>
                    <button class="btn btn-delete" data-id="${pedido.id_pedido}">Eliminar</button>
                </td>
            `;
            tbody.appendChild(row);
        });

        pedidosTableContainer.innerHTML = '';
        pedidosTableContainer.appendChild(table);

        // Añadir listeners para los botones de eliminar
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', (e) => {
                const id = e.target.dataset.id;
                if (confirm(`¿Estás seguro de que deseas eliminar el pedido #${id}?`)) {
                    deletePedido(id);
                }
            });
        });
    }

    async function deletePedido(id) {
        try {
            const response = await fetch('../api/pedidos_admin_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id_pedido: id })
            });
            const result = await response.json();
            
            if (result.success) {
                alert(result.message);
                fetchPedidos(); // Recargar la lista de pedidos
            } else {
                alert(`Error al eliminar el pedido: ${result.message}`);
            }
        } catch (error) {
            alert('Error de conexión o del servidor.');
            console.error('Error deleting pedido:', error);
        }
    }
});