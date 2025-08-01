﻿# Resolucion-incidencia
 
La refactorización se centró en tres áreas: **seguridad**, **robustez** y **mantenibilidad**.

### Diagnóstico de Problemas en el Código Original

#### 1. Vulnerabilidad Crítica: Inyección SQL (SQL Injection)

* **Problema:** El código construía la consulta SQL concatenando directamente una variable (`$role`) que provenía de la entrada del usuario (`$_GET`).
    ```
    $role = $_GET['role'];
    $sql = "SELECT * FROM users WHERE role = '$role'";
    ```
* **Riesgo:** Un atacante podía manipular el parámetro `role` en la URL para alterar la consulta SQL. Por ejemplo, si un atacante enviaba `?role=' OR '1'='1`, la consulta resultante sería:
    ```
    SELECT * FROM users WHERE role = '' OR '1'='1'
    ```
    Esta consulta devolvería **todos los usuarios** de la tabla, ignorando el filtro de rol. Ataques más sofisticados podrían permitir extraer, modificar o eliminar datos de toda la base de datos.

#### 2. Falla de Seguridad: Credenciales en el Código (Hardcoded Credentials)

* **Problema:** Las credenciales de la base de datos (`"root"`, `""`) estaban escritas directamente en el código.
    ```
    $conn = new mysqli("localhost", "root", "", "plataforma");
    ```
* **Riesgo:** Si el código fuente se filtra (por ejemplo, a través de un error de configuración del servidor o un repositorio público), las credenciales quedan expuestas. Además, se utilizaba el usuario `root`, que tiene todos los privilegios, cuando una aplicación solo necesita permisos limitados (CRUD) sobre tablas específicas.

#### 3. Malas Prácticas de Programación y Mantenibilidad

* **Falta de Validación de Entradas:** El código no verificaba si el parámetro `role` existía o estaba vacío, asumiendo ciegamente que siempre estaría presente y sería válido.
* **Uso de `SELECT *`:** Es ineficiente y frágil. Se transfiere más data de la necesaria y si la estructura de la tabla `users` cambia (por ejemplo, se añade una columna grande), el rendimiento de la aplicación se degrada sin haber modificado el código.
* **Ausencia de Manejo de Errores:** Si la conexión a la base de datos o la consulta fallaban, el script terminaría abruptamente, mostrando un error de PHP al usuario. Estos errores pueden filtrar información sensible sobre la estructura del servidor y el código.
* **Falta de Estructura (Código Spaguetti):** Toda la lógica (conexión, procesamiento y respuesta) estaba en un único bloque secuencial, lo que dificulta su lectura, reutilización y la realización de pruebas unitarias.

### Justificación de la Solución Refactorizada

#### 1. Prevención de Inyección SQL con Consultas Preparadas

* **Solución:** Se reemplazó la concatenación de cadenas por **consultas preparadas** (`prepared statements`) usando `prepare()`, `bind_param()` y `execute()`.
    ```
    $sql = "SELECT id, username, firstname, lastname, email FROM users WHERE role = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $role);
    $stmt->execute();
    ```
* **Justificación:** Este método separa la estructura de la consulta SQL de los datos proporcionados por el usuario. La base de datos recibe la plantilla de la consulta por un lado y los datos por otro, tratándolos siempre como valores literales y nunca como parte ejecutable de la consulta. **Esto elimina por completo el riesgo de Inyección SQL.**

#### 2. Gestión Segura de Credenciales y Conexión

* **Solución:** Las credenciales se movieron a un archivo `config.php` y se cargan como constantes. Se recomienda explícitamente que este archivo se almacene **fuera del directorio raíz público** del servidor.
* **Justificación:** Separar la configuración del código de la aplicación es una práctica estándar. Si el código se expone, las credenciales permanecen seguras. Además, se promueve el uso de un usuario de base de datos con privilegios mínimos (`app_user`) en lugar de `root`.

#### 3. Código Robusto y Mantenible

* **Validación de Entrada:** Se verifica que `$_GET['role']` exista y no esté vacío antes de ejecutar cualquier lógica de base de datos. Si no es válido, se devuelve un error `400 Bad Request`.
* **Manejo de Errores:** Se utiliza un bloque `try...catch` y se comprueban los posibles fallos en la conexión y preparación de la consulta. En caso de error, se envía una respuesta JSON genérica con un código de estado HTTP `500 Internal Server Error`, evitando filtrar detalles internos.
* **Estructura Funcional:** La lógica se ha organizado en funciones (`get_db_connection`, `send_json_response`), lo que mejora la legibilidad, evita la duplicación de código y facilita las pruebas.
* **Selección Explícita de Columnas:** Se reemplazó `SELECT *` por `SELECT id, username, firstname, lastname, email`, mejorando el rendimiento y haciendo el código más explícito y robusto ante cambios en la base de datos.
* **Cierre de Recursos:** Se utiliza un bloque `finally` para garantizar que la conexión a la base de datos (`$conn`) y el `statement` (`$stmt`) se cierren siempre, incluso si ocurre un error, liberando recursos del servidor.
