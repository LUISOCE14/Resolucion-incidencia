/**
 * Fichero: config.php
 * Este archivo debe estar fuera del directorio raíz del servidor web (fuera de public_html o www)
 * para que no sea accesible desde el navegador.
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'app_user'); // Usuario con permisos limitados, no 'root'
define('DB_PASS', 'ContraseñaSegura_#2024');
define('DB_NAME', 'plataforma');

// --- Fin de config.php ---