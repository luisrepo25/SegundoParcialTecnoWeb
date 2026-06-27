# Instituto San Pablo

Este proyecto utiliza **Laravel** en el backend y **Vue.js** (mediante Inertia.js) en el frontend. Todo funciona dentro del mismo repositorio bajo una arquitectura monolítica moderna.

## Requisitos Previos

Asegúrate de tener instalado lo siguiente en tu máquina:
- **PHP** (8.2 o superior)
- **Composer**
- **Node.js y npm**
- **Git**

## Pasos para la Instalación

Sigue estos pasos para levantar el proyecto por primera vez en tu computadora:

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/luisrepo25/SegundoParcialTecnoWeb.git
   cd SegundoParcialTecnoWeb
   ```

2. **Instalar dependencias de PHP (Backend):**
   ```bash
   composer install
   ```

3. **Instalar dependencias de Node.js (Frontend):**
   ```bash
   npm install
   ```

4. **Configurar las variables de entorno:**
   - Duplica el archivo de ejemplo `.env.example` y renómbralo a `.env`.
   - En Windows (PowerShell/CMD): `copy .env.example .env`
   - En Mac/Linux/GitBash: `cp .env.example .env`
   - *Nota: Si usas SQLite, asegúrate de que exista el archivo `database/database.sqlite` (puedes crearlo vacío si no existe) o configura tu conexión a MySQL/PostgreSQL en el `.env`.*

5. **Generar la clave de la aplicación:**
   ```bash
   php artisan key:generate
   ```

6. **Ejecutar las migraciones de la base de datos:**
   ```bash
   php artisan migrate --seed
   ```

---

## 🚀 Cómo ejecutar el proyecto para trabajar

Dado que usamos Laravel y Vue con Vite, **es obligatorio mantener dos terminales abiertas** corriendo al mismo tiempo:

### Terminal 1 (Servidor de Backend - PHP):
Esta terminal se encargará de levantar el servidor de Laravel y la base de datos.
```bash
php artisan serve
```

### Terminal 2 (Servidor de Frontend - Vue/Vite):
Esta terminal compilará en tiempo real los cambios que hagas en los archivos `.vue`.
```bash
npm run dev
```

Una vez que ambos comandos estén corriendo, puedes abrir la aplicación en tu navegador accediendo a:
**[http://localhost:8000](http://localhost:8000)**

---

## 🧪 Ejecutar Pruebas (Tests)

El proyecto cuenta con pruebas automatizadas en la carpeta `tests/`. Para verificar que todo funciona correctamente o si hiciste algún cambio que rompió algo, ejecuta:

```bash
php artisan test
```
