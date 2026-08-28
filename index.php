<?php
require_once __DIR__ . '/config/config.php';

if (esta_autenticado()) {
    redirigir(BASE_URL . '/modules/dashboard.php');
} else {
    redirigir(BASE_URL . '/public/login.php');
}
