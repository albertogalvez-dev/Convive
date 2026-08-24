<?php

declare(strict_types=1);

use App\Kernel;
use Nelmio\ApiDocBundle\ApiDocGenerator;
use OpenApi\Annotations\OpenApi;
use OpenApi\Undefined;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

const OPENAPI_HTTP_METHODS = ['GET', 'PUT', 'POST', 'DELETE', 'OPTIONS', 'HEAD', 'PATCH', 'TRACE'];

$environment = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev';
$kernel = new Kernel($environment, false);
$kernel->boot();

try {
    $container = $kernel->getContainer();
    $generator = $container->get('nelmio_api_doc.generator');
    $router = $container->get('router');

    if (!$generator instanceof ApiDocGenerator) {
        throw new RuntimeException('Nelmio OpenAPI generator is unavailable.');
    }
    if (!method_exists($router, 'getRouteCollection')) {
        throw new RuntimeException('Symfony router route collection is unavailable.');
    }

    /** @var RouteCollection $routes */
    $routes = $router->getRouteCollection();
    $openApi = $generator->generate();

    assertCompleteCoverage($openApi, $routes);
    assertClosedObjectSchemas($openApi);

    if (in_array('--exercise', $_SERVER['argv'], true)) {
        exerciseMissingRouteDetection($openApi, $routes, $environment);
    }

    fwrite(STDOUT, "Every /api/v1 router operation is present in the generated OpenAPI contract.\n");
    fwrite(STDOUT, "Every generated object schema explicitly forbids additional properties.\n");
} finally {
    $kernel->shutdown();
}

function assertCompleteCoverage(OpenApi $openApi, RouteCollection $routes): void
{
    $missingOperations = missingOperations($openApi, $routes);
    if ([] === $missingOperations) {
        return;
    }

    fwrite(STDERR, "Router operations absent from the generated OpenAPI contract:\n");
    foreach ($missingOperations as $missingOperation) {
        fwrite(STDERR, sprintf("- %s %s (%s)\n", $missingOperation['method'], $missingOperation['path'], $missingOperation['route']));
    }

    exit(1);
}

/** @return list<array{method: string, path: string, route: string}> */
function missingOperations(OpenApi $openApi, RouteCollection $routes): array
{
    $documentedOperations = documentedOperations($openApi);
    $missingOperations = [];

    foreach ($routes->all() as $routeName => $route) {
        $path = normaliseRoutePath($route->getPath());
        if (!str_starts_with($path, '/api/v1')) {
            continue;
        }

        $methods = $route->getMethods();
        if ([] === $methods) {
            $methods = OPENAPI_HTTP_METHODS;
        }

        foreach ($methods as $method) {
            $method = strtoupper($method);
            if (!in_array($method, OPENAPI_HTTP_METHODS, true)) {
                continue;
            }

            if (!isset($documentedOperations[$method.' '.$path])) {
                $missingOperations[] = [
                    'method' => $method,
                    'path' => $path,
                    'route' => $routeName,
                ];
            }
        }
    }

    usort(
        $missingOperations,
        static fn (array $left, array $right): int => [$left['path'], $left['method']] <=> [$right['path'], $right['method']],
    );

    return $missingOperations;
}

/** @return array<string, true> */
function documentedOperations(OpenApi $openApi): array
{
    $operations = [];

    foreach ($openApi->paths as $pathItem) {
        $path = $pathItem->path;
        if (!is_string($path)) {
            continue;
        }

        foreach (OPENAPI_HTTP_METHODS as $method) {
            $operation = $pathItem->{strtolower($method)};
            if (Undefined::UNDEFINED !== $operation) {
                $operations[$method.' '.$path] = true;
            }
        }
    }

    return $operations;
}

function normaliseRoutePath(string $path): string
{
    return str_ends_with($path, '.{_format}') ? substr($path, 0, -10) : $path;
}

function assertClosedObjectSchemas(OpenApi $openApi): void
{
    $document = json_decode($openApi->toJson(), true, 512, JSON_THROW_ON_ERROR);
    $openObjectSchemas = [];
    findOpenObjectSchemas($document, '$', $openObjectSchemas);

    if ([] === $openObjectSchemas) {
        return;
    }

    fwrite(STDERR, "OpenAPI object schemas must explicitly set additionalProperties: false:\n");
    foreach ($openObjectSchemas as $schemaPath) {
        fwrite(STDERR, "- {$schemaPath}\n");
    }

    exit(1);
}

/** @param array<string, mixed> $node @param list<string> $openObjectSchemas */
function findOpenObjectSchemas(array $node, string $path, array &$openObjectSchemas): void
{
    if ('object' === ($node['type'] ?? null) && false !== ($node['additionalProperties'] ?? null)) {
        $openObjectSchemas[] = $path;
    }

    foreach ($node as $key => $value) {
        if (is_array($value)) {
            findOpenObjectSchemas($value, $path.'.'.$key, $openObjectSchemas);
        }
    }
}

function exerciseMissingRouteDetection(OpenApi $openApi, RouteCollection $routes, string $environment): void
{
    if ('test' !== $environment) {
        throw new RuntimeException('The OpenAPI route-coverage exercise is only allowed in APP_ENV=test.');
    }

    $exerciseRouteName = 'openapi_route_coverage_exercise';
    $routes->add($exerciseRouteName, new Route('/api/v1/openapi-route-coverage-exercise', methods: ['GET']));

    try {
        $missingOperations = missingOperations($openApi, $routes);
        $exerciseIsDetected = array_filter(
            $missingOperations,
            static fn (array $missingOperation): bool => 'GET' === $missingOperation['method']
                && '/api/v1/openapi-route-coverage-exercise' === $missingOperation['path']
                && $exerciseRouteName === $missingOperation['route'],
        );

        if ([] === $exerciseIsDetected) {
            throw new RuntimeException('The OpenAPI route-coverage guard did not detect the deliberately undocumented route.');
        }
    } finally {
        $routes->remove($exerciseRouteName);
    }

    fwrite(STDOUT, "OpenAPI route-coverage regression exercise detected an undocumented route.\n");
}
