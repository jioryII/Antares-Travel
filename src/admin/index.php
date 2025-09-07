<?php
/**
 * Punto de entrada principal del panel de administración
 * Redirige automáticamente al dashboard
 */

// Redirección simple y directa al dashboard
header("Location: pages/dashboard/", true, 302);
exit();
?>
