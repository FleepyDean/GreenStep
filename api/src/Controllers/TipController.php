<?php

namespace App\Controllers;

use App\Models\Tip;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TipController
{
    private Tip $tip;

    public function __construct()
    {
        $this->tip = new Tip();
    }

    // USER
    public function getRandom(Request $request, Response $response)
    {
        $tip = $this->tip->getRandom();

        $response->getBody()->write(
            json_encode($tip)
        );

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );
    }

    // ADMIN
    public function getAll(Request $request, Response $response)
    {
        $tips = $this->tip->getAll();

        $response->getBody()->write(
            json_encode($tips)
        );

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );
    }

    public function create(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        if (($user['role'] ?? '') !== 'admin') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Admin access required'
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        $data = json_decode(
            $request->getBody()->getContents(),
            true
        );

        $this->tip->create(
            $data['title'],
            $data['body'],
            $data['category'],
            $data['source_url'] ?? null
        );

        $response->getBody()->write(
            json_encode([
                "message" => "Tip added"
            ])
        );

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );
    }

    public function update(Request $request, Response $response, array $args)
    {
        $user = $request->getAttribute('user');

        if (($user['role'] ?? '') !== 'admin') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Admin access required'
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        $data = json_decode(
            $request->getBody()->getContents(),
            true
        );

        $this->tip->update(
            $args['id'],
            $data['title'],
            $data['body'],
            $data['category'],
            $data['source_url'] ?? null
        );

        $response->getBody()->write(
            json_encode([
                "success" => true,
                "message" => "Tip updated"
            ])
        );

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );
    }

    public function delete(Request $request, Response $response, array $args)
    {
        $user = $request->getAttribute('user');

        if (($user['role'] ?? '') !== 'admin') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Admin access required'
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        $this->tip->delete($args['id']);

        $response->getBody()->write(
            json_encode([
                "message" => "Tip deleted"
            ])
        );

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );
    }
}