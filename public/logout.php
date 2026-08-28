<?php
require_once __DIR__ . '/../config/config.php';
cerrar_sesion();
redirigir(BASE_URL . '/public/login.php');
