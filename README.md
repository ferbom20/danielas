# Sistema de Control de Estacionamiento Residencial

Sistema web completo (PHP 8+, MySQL, HTML5, CSS3, JS vanilla) para el control
de acceso de un estacionamiento residencial de **6 torres y 46 puestos**
(10 de ellos exclusivos para visitantes).

## 🚀 Instalación en XAMPP / Laragon

1. Copie toda la carpeta del proyecto dentro de `htdocs` (XAMPP) o `www` (Laragon),
   por ejemplo: `C:\xampp\htdocs\estacionamiento`.

2. Cree la base de datos importando el script SQL:
   - Abra **phpMyAdmin**.
   - Cree una nueva base de datos o simplemente importe el archivo
     `database/database.sql` (el script ya crea la base de datos
     `estacionamiento_db` automáticamente con `CREATE DATABASE IF NOT EXISTS`).

3. Verifique las credenciales de conexión en `config/database.php`
   (por defecto: host `localhost`, usuario `root`, sin contraseña — el estándar
   de XAMPP/Laragon).

4. Abra en el navegador: `http://localhost/estacionamiento/` (ajuste el nombre
   de carpeta según corresponda). El sistema le redirigirá automáticamente
   al login.

## 🔑 Usuarios de prueba

| Usuario  | Contraseña   | Rol      |
|----------|--------------|----------|
| admin    | Admin123!    | admin    |
| garita   | Garita123!   | garita   |

> Cambie estas contraseñas antes de usar el sistema en producción
> (puede generar nuevos hashes con `password_hash()` en PHP y actualizarlos
> directamente en la tabla `usuarios`).

## 📂 Estructura del proyecto

```
/config      → Configuración de conexión y constantes del sistema
/database    → Script SQL con esquema completo y datos iniciales
/public      → Login, logout y página pública de consulta QR
/assets/css  → Estilos (Bento Grid, Poppins, gradientes)
/assets/js   → Lógica de cada módulo + librería QR (vendor, MIT license)
/includes    → Header, sidebar, footer, autenticación, CSRF, funciones
/modules     → Dashboard, Garita, Salidas, Puestos, Personas, Vehículos, Historial
/api         → Endpoints JSON usados por el frontend (todas protegidas por sesión + CSRF)
index.php    → Punto de entrada (redirige según sesión)
```

## 🧠 Lógica de negocio

- **Residente / Visitante:** gratis hasta 8 horas; tarifa plana de $1 si se supera.
- **Mercado / Mudanza:** exclusivo residentes; gratis hasta 30 minutos; $1 si se supera.
- El monto **nunca se digita manualmente**: se calcula en el servidor
  (`includes/functions.php → calcular_estadia()`).
- Cada persona se registra **una sola vez** (cédula única). Al volver a
  ingresar, el encargado busca por cédula o placa y el sistema autocompleta
  sus datos y vehículos registrados.

## 🔒 Seguridad implementada

- PDO con consultas preparadas en **todas** las operaciones de base de datos.
- Contraseñas con `password_hash()` / `password_verify()`.
- Protección CSRF en todos los formularios y peticiones POST (token de sesión).
- Sesiones con `httponly`, `samesite=Lax` y regeneración periódica de ID.
- Control de acceso por roles (`admin` vs `garita`) en operaciones sensibles
  (p. ej. eliminar personas/vehículos).
- Tokens QR de 64 caracteres hexadecimales (256 bits) generados con
  `random_bytes()` — computacionalmente imposibles de adivinar. El QR **no**
  contiene datos personales, solo el token opaco.
- Transacciones con `SELECT ... FOR UPDATE` al registrar entradas/salidas
  para evitar condiciones de carrera (doble asignación de puesto, doble
  entrada activa del mismo vehículo).

## 📱 Consulta pública por QR

Cada persona registrada tiene un QR único (generado 100% en el navegador con
una librería vendorizada MIT, sin conexión a servicios externos) que enlaza a
`public/consulta.php?t=TOKEN`. Esa página, sin necesidad de iniciar sesión,
muestra la estadía activa (o indica que no hay ninguna) y actualiza el
contador cada 5 segundos vía JavaScript, sin recargar la página.
=======
