<?php

namespace Exolnet\Test\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;

trait AssertionsTrait
{
    /**
     * Asserts if two arrays have similar values, sorting them before the fact
     * in order to "ignore" ordering.
     *
     * @param array  $expected
     * @param array  $actual
     * @param string $message
     */
    protected function assertArrayValuesEquals(array $expected, array $actual, string $message = ''): void
    {
        $this->assertEqualsCanonicalizing($expected, $actual, $message);
    }

    /**
     * @param string $viewName
     * @param string|null $message
     */
    public function assertViewExists(string $viewName, ?string $message = null): void
    {
        try {
            View::make($viewName);
            $this->assertTrue(true);
        } catch (InvalidArgumentException $e) {
            $message = $message ?: sprintf('The view %s was not found.', $viewName);
            $this->fail($message);
        }
    }

    /**
     * @param string $method
     * @param string $uri
     * @param string|null $message
     */
    public function assertRouteExists(string $method, string $uri, ?string $message = null): void
    {
        $message = $message ?: sprintf('The route %s %s was not found.', strtoupper($method), $uri);

        // Create a corresponding request
        $request = Request::create($uri, $method);

        // Match the request to a route
        $route = $this->app['router']->getRoutes()->match($request);
        $this->assertNotNull($route, $message);
    }

    /**
     * @param mixed $response
     */
    public function assertIsViewResponse($response): void
    {
        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $response);
    }

    /**
     * @param mixed $response
     */
    public function assertIsRedirectResponse($response): void
    {
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /**
     * @param mixed $response
     * @param string $uri
     * @param array $with
     */
    public function assertResponseRedirectedTo($response, string $uri, array $with = []): void
    {
        $this->assertIsRedirectResponse($response);

        $this->assertEquals($this->app['url']->to($uri), $response->headers->get('Location'));

        $this->assertSessionHasAll($with);
    }

    /**
     * @param mixed $response
     * @param string $name
     * @param array $parameters
     * @param array $with
     */
    public function assertResponseRedirectedToRoute(
        $response,
        string $name,
        array $parameters = [],
        array $with = []
    ): void {
        $this->assertResponseRedirectedTo($response, $this->app['url']->route($name, $parameters), $with);
    }

    /**
     * @param mixed $response
     * @param string $name
     * @param array $parameters
     * @param array $with
     */
    public function assertResponseRedirectedToAction(
        $response,
        string $name,
        array $parameters = [],
        array $with = []
    ): void {
        $this->assertResponseRedirectedTo($response, $this->app['url']->action($name, $parameters), $with);
    }

    /**
     * @param mixed $response
     */
    public function assertIsJsonResponse($response): void
    {
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
    }

    /**
     * @param mixed $response
     */
    public function assertIsStreamResponse($response): void
    {
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }
}
