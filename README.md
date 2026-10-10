# 🍽️ Sistema de Gestión de Pedidos e Inventario

Un sistema web dinámico y modular diseñado para la administración integral de pedidos, inventario, control de mesas y reportes analíticos en establecimientos gastronómicos o de servicios.

---

## 🚀 Características Principales

- **Gestión de Roles y Permisos:** Control de acceso granular para Administradores, Cajeros y Personal de Cocina (`Admin`, `cajero`, `cocina`).
- **Dashboard e Indicadores (KPIs):** Visualización en tiempo real de ventas totales, número de pedidos, ticket promedio e historial de actividad.
- **Control de Inventario y Recetas:** 
  - Gestión de ingredientes, unidades de medida y alertas automáticas de *stock* bajo/agotado.
  - Registro de movimientos de inventario (Entradas, Salidas, Ajustes).
  - Cálculo de costos por receta y márgenes de ganancia.
- **Reportes Analíticos y Gráficos:** Gráficas interactivas (Chart.js) de ventas mensuales, distribución de servicios y métodos de pago, con opción de exportación a PDF.
- **Módulo de Administración:** CRUD completo para la gestión de usuarios, productos, categorías y clientes.

---

## 🛠️ Tecnologías Utilizadas

- **Backend:** PHP (PDO, Arquitectura orientada a scripts y servicios API).
- **Base de Datos:** MySQL / MariaDB.
- **Frontend:** HTML5, CSS3 (Variables CSS, Flexbox/Grid), JavaScript ES6+ (Fetch API, manipulación asíncrona del DOM).
- **Librerías Externas:** 
  - [Chart.js](https://www.chartjs.org/) (Gráficos interactivos)
  - [FontAwesome](https://fontawesome.com/) (Iconografía)

---

## 📂 Estructura del Proyecto

```text
sistema_pedidos/
├── admin/               # Modulos de administración (Dashboard, Inventario, Reportes, Gestión)
├── api/                 # Endpoints JSON para llamadas asíncronas
├── assets/              # Archivos estáticos (CSS, JavaScript, Imágenes)
├── database/            # Scripts SQL e importación de la base de datos
├── includes/            # Componentes reutilizables (Conexión PDO, Navbar, Funciones auxiliares)
└── README.md
