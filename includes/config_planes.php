<?php
// includes/config_planes.php

function obtenerLimitesPlan($plan_id) {
    switch ($plan_id) {
        case 1: // Emprendedor ($3.990)
            return [
                'nombre' => 'Emprendedor',
                'limite_pubs' => 15,
                'limite_alertas' => 9999, // Ilimitado
                'precio' => '$3.990'
            ];
        case 2: // Pyme ($6.990)
            return [
                'nombre' => 'Pyme',
                'limite_pubs' => 9999, // Ilimitado
                'limite_alertas' => 9999, // Ilimitado
                'precio' => '$6.990'
            ];
        default: // Gratis (0)
            return [
                'nombre' => 'Vecino (Gratis)',
                'limite_pubs' => 3,
                'limite_alertas' => 9999, // Siempre ilimitado por seguridad
                'precio' => 'Gratis'
            ];
    }
}
?>