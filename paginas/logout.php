<?php
session_start(); // Iniciar la sesión (si no se ha iniciado ya)
session_destroy();
header("Location: ../index.php"); 
// No necesitas más código aquí.  La función logoutUser() se encarga de todo.

?>
