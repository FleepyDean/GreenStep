<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HealthController
{
    public function check(Request $request, Response $response): Response
    {
        $dbStatus = Database::testConnection();
        
        $data = [
            'message' => 'GreenStep API v1.0',
            'status' => 'running',
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => $dbStatus ? 'connected' : 'disconnected'
        ];
        
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
