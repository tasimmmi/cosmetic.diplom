<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AliceController;
use App\Core\Request;
use App\Core\Response;

$controller = new AliceController();
$request = new Request();
$response = new Response();

$controller->webhook($request, $response);