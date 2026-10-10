document.addEventListener('DOMContentLoaded', function() {
    console.log('Script de prueba de productos cargado.');

    const productosList = document.getElementById('productos-list');

    async function cargarProductos() {
        console.log('Intentando cargar productos...');
        productosList.innerHTML = '<p>Cargando productos...</p>';

        try {
            const response = await fetch('../api/productos.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({}) });
            const result = await response.json();

            console.log('Respuesta de la API de productos:', result);

            if (result.success && result.data.length > 0) {
                productosList.innerHTML = '';
                result.data.forEach(categoria => {
                    const categoriaDiv = document.createElement('div');
                    categoriaDiv.className = 'categoria';
                    categoriaDiv.innerHTML = `<h4>${categoria.nombre_categoria}</h4>`;
                    categoria.productos.forEach(producto => {
                        const productItem = document.createElement('div');
                        productItem.className = 'product-item';
                        productItem.innerHTML = `
                            <div class="product-info">
                                <h5>${producto.nombre_producto}</h5>
                                <p><strong>$${parseFloat(producto.precio).toFixed(2)}</strong></p>
                            </div>
                            <div class="product-controls">
                                <input type="number" value="0" min="0" data-id="${producto.id_producto}" data-price="${producto.precio}" class="product-qty">
                            </div>
                        `;
                        categoriaDiv.appendChild(productItem);
                    });
                    productosList.appendChild(categoriaDiv);
                });
            } else {
                productosList.innerHTML = `<p>${result.message || 'No se encontraron productos disponibles.'}</p>`;
            }
        } catch (error) {
            console.error('Error en la carga de productos:', error);
            productosList.innerHTML = `<p>Error al cargar los productos.</p>`;
        }
    }
    cargarProductos();
});