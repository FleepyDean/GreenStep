<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Admin;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\CarbonDatasetService;

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

    public function dataset($request, $response)
    {
        $info = CarbonDatasetService::getDatasetInfo();

        $response->getBody()->write(json_encode([
            "success" => true,
            "data" => $info
        ]));

        return $response
            ->withHeader("Content-Type","application/json");
    }
}