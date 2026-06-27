<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Admin;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminController
{
    private Admin $adminModel;

    public function __construct()
    {
        $this->adminModel = new Admin();
    }

    public function dashboard(Request $request, Response $response): Response
    {
        $stats = $this->adminModel->getDashboardStats();

        $response->getBody()->write(json_encode([
            "success" => true,
            "data" => $stats
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}