<?php

namespace Exolnet\Test\Traits;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
    protected function assertArrayValuesEquals(array $expected, array $actual, $message = '')
    {
        $this->assertEqualsCanonicalizing($expected, $actual, $message);
    }

    /**
     * @param string $view_name
     * @param string $message
     */
    public function assertViewExists(string $view_name, string $message = 'The view %s was not found.'): void
    {
        try {
            View::make($view_name);
            $this->assertTrue(true);
        } catch (InvalidArgumentException $e) {
            $this->fail(sprintf($message, $view_name));
        }
    }

    /**
     * @param string $method
     * @param string $uri
     * @param string $message
     */
    public function assertRouteExists(
        string $method,
        string $uri,
        string $message = 'The route %s %s was not found.'
    ): void {
        $message = $message ?: sprintf($message, strtoupper($method), $uri);

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
    public function assertResponseRedirectedTo($response, string $uri, array $with = [])
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
    public function assertIsStreamResponse($response)
    {
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }
}
