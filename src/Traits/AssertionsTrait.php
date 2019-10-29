<?php

namespace Exolnet\Test\Traits;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait AssertionsTrait
{
    public function assertViewExists($view_name, $message = 'The view %s was not found.')
    {
        try {
            View::make($view_name);
            $this->assertTrue(true);
        } catch (InvalidArgumentException $e) {
            $this->fail(sprintf($message, $view_name));
        }
    }

    public function assertHttpException($expectedStatusCode, Closure $testCase)
    {
        try {
            $testCase($this);

            $this->assertFalse(true, "An HttpException should have been thrown by the provided Closure.");
        } catch (HttpException $e) {
            // assertResponseStatus() won't work because the response object is null
            $this->assertEquals(
                $expectedStatusCode,
                $e->getStatusCode(),
                sprintf("Expected an HTTP status of %d but got %d.", $expectedStatusCode, $e->getStatusCode())
            );
        }
    }

    public function expectResponseAccessDenied(Closure $testCase)
    {
        $this->assertHttpException(403, $testCase);
    }

    public function expectResponseMissing()
    {
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
    }

    public function assertResponseContentType($expected)
    {
        $response = $this->response;

        $actual = $response->headers->get('Content-type');

        return $this->assertEquals($expected, $actual, 'Expected response ' . $expected . ', got ' . $actual . '.');
    }

    public function assertResponseJson()
    {
        return $this->assertResponseContentType('application/json');
    }

    public function assertRouteExists($method, $uri, $message = null)
    {
        $message = $message ?: sprintf('The route %s %s was not found.', strtoupper($method), $uri);

        // Create a corresponding request
        $request = Request::create($uri, $method);

        // Match the request to a route
        $route = $this->app['router']->getRoutes()->match($request);
        $this->assertNotNull($route, $message);
    }

    /**
     * @deprecated
     */
    public function assertRouteMatchesAction($method, $uri, $action, $message = null)
    {
        $this->assertTrue(true);
    }

    public function assertIsViewResponse($response)
    {
        $this->assertInstanceOf('Illuminate\View\View', $response);
    }

    public function assertIsRedirectResponse($response)
    {
        $this->assertInstanceOf('Illuminate\Http\RedirectResponse', $response);
    }

    public function assertResponseRedirectedTo($response, $uri, $with = [])
    {
        $this->assertIsRedirectResponse($response);

        $this->assertEquals($this->app['url']->to($uri), $response->headers->get('Location'));

        $this->assertSessionHasAll($with);
    }

    public function assertResponseRedirectedToRoute($response, $name, $parameters = [], $with = [])
    {
        $this->assertResponseRedirectedTo($response, $this->app['url']->route($name, $parameters), $with);
    }

    public function assertResponseRedirectedToAction($response, $name, $parameters = [], $with = [])
    {
        $this->assertResponseRedirectedTo($response, $this->app['url']->action($name, $parameters), $with);
    }

    public function assertIsJsonResponse($response)
    {
        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);
    }

    public function assertIsStreamResponse($response)
    {
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\StreamedResponse', $response);
    }

    public function assertNotice($type)
    {
        $this->assertSessionHas('notice_' . $type);
    }

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
     * @param string $selector
     * @return $this
     */
    public function seeSelector($selector)
    {
        $elements = $this->crawler->filter($selector);

        $this->assertTrue(count($elements) > 0);

        return $this;
    }

    /**
     * @param $kind
     * @return \Exolnet\Test\Traits\AssertionsTrait
     */
    public function seeAlert($kind)
    {
        return $this->seeSelector('.alert.alert-' . $kind);
    }
}
