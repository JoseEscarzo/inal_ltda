============================================================
 SISTEMA INAL LTDA - INSTRUCCIONES DE INSTALACION
============================================================

REQUISITOS PREVIOS
- PHP 8.2 o superior (con extensiones pdo_pgsql y pgsql activadas)
- Composer
- Node.js 18 o superior (incluye npm)
- PostgreSQL 14 o superior
- Python 3.10, 3.11 o 3.12  (NO usar 3.13 o superior: torch no es compatible)

ESTRUCTURA
- backend/          API en Laravel (PHP)
- frontend/         Interfaz web (Node / npm)
- camera-service/   Servicio de IA con YOLOv8 (Python)
- postman/          Coleccion de pruebas de la API
- BaseDeDatos/      Respaldo de la base de datos (opcional)
- Documento/        Documento del Proyecto Integrador

------------------------------------------------------------
1) BASE DE DATOS (PostgreSQL)
------------------------------------------------------------
Crear una base de datos vacia llamada: inal_ltda

   psql -U postgres -c "CREATE DATABASE inal_ltda;"

------------------------------------------------------------
2) BACKEND (Laravel)  ->  http://localhost:8000
------------------------------------------------------------
   cd backend
   composer install
   copy .env.example .env

Abrir el archivo .env y configurar:
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=inal_ltda
   DB_USERNAME=postgres
   DB_PASSWORD=<contrasena de PostgreSQL del evaluador>

(Opcional, solo para envio de correos: MAIL_USERNAME, MAIL_PASSWORD
con una contrasena de aplicacion de Gmail.)

Continuar con:
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve

"migrate --seed" crea todas las tablas y ejecuta los seeders
(roles y usuario administrador).

Si se desea reiniciar la base desde cero:
   php artisan migrate:fresh --seed

------------------------------------------------------------
3) CUENTA INICIAL (creada por el seeder)
------------------------------------------------------------
   Correo:      stichjose0525@gmail.com
   Contrasena:  Jose1234
   Rol:         Administrador (id_rol = 1)

------------------------------------------------------------
4) FRONTEND  ->  http://localhost:5173
------------------------------------------------------------
Abrir otra terminal:
   cd frontend
   npm install
   npm run dev

------------------------------------------------------------
5) SERVICIO DE IA - YOLOv8 / ULTRALYTICS  ->  http://localhost:5001
------------------------------------------------------------
Abrir otra terminal (PowerShell):
   cd camera-service
   python -m venv .venv
   .\.venv\Scripts\Activate.ps1
   pip install torch torchvision --index-url https://download.pytorch.org/whl/cpu
   pip install -r requirements.txt
   python main.py

Notas:
- Si PowerShell bloquea la activacion del entorno, ejecutar antes:
     Set-ExecutionPolicy -Scope Process -ExecutionPolicy RemoteSigned
- La instalacion tarda varios minutos (descarga de torch y ultralytics).
- Siempre ejecutar "python main.py" con el entorno (.venv) activado,
  de lo contrario aparece el error "No module named 'ultralytics'".
- El modelo esta en la carpeta camera-service/models y yolov8n.pt.

------------------------------------------------------------
6) ORDEN DE EJECUCION
------------------------------------------------------------
   1. PostgreSQL encendido
   2. Backend:         php artisan serve
   3. Servicio de IA:  python main.py
   4. Frontend:        npm run dev
   5. Abrir http://localhost:5173 e iniciar sesion con la cuenta
      del punto 3.

------------------------------------------------------------
SOLUCION DE PROBLEMAS
------------------------------------------------------------
- "vendor/autoload.php not found"      -> ejecutar composer install
- "No module named 'ultralytics'"      -> activar .venv e instalar requirements
- Error de conexion a la base de datos -> revisar DB_PASSWORD en .env
============================================================
