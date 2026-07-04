<?php

use Illuminate\Support\Facades\Route;

it('adds an X-Request-Id header to web responses', function () {
    $response = $this->get('/');

    $response->assertHeader('X-Request-Id');
    expect($response->headers->get('X-Request-Id'))->not->toBeEmpty();
});

it('honors an inbound X-Request-Id header and echoes it back unchanged', function () {
    $response = $this->get('/', ['X-Request-Id' => 'test-correlation-123']);

    $response->assertHeader('X-Request-Id', 'test-correlation-123');
});

it('generates a valid UUID when no X-Request-Id is supplied', function () {
    $response = $this->get('/');

    expect($response->headers->get('X-Request-Id'))
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

it('adds an X-Request-Id header to api responses even on a 401', function () {
    $response = $this->getJson('/api/user');

    $response->assertUnauthorized();
    $response->assertHeader('X-Request-Id');
    expect($response->headers->get('X-Request-Id'))->not->toBeEmpty();
});

it('echoes the X-Request-Id on error responses when an exception aborts the pipeline', function () {
    Route::get('/__requestid_boom', function () {
        throw new RuntimeException('boom');
    });

    $response = $this->get('/__requestid_boom', ['X-Request-Id' => 'error-correlation-456']);

    $response->assertServerError();
    $response->assertHeader('X-Request-Id', 'error-correlation-456');
});
