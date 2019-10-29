<?php

namespace Exolnet\Test;

use BadMethodCallException;
use Exolnet\Test\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * phpcs:disable
 * @method \Illuminate\Http\Response getAjax($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
 * @method \Illuminate\Http\Response postAjax($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
 * @method \Illuminate\Http\Response putAjax($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
 * @method \Illuminate\Http\Response patchAjax($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
 * @method \Illuminate\Http\Response deleteAjax($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
 * phpcs:enable
 */
abstract class TestCaseFunctional extends BaseTestCase
{
    protected function displayErrors()
    {
        $errors = $this->app['session.store']->get('notice_error');
        $errors = $errors ?: $this->app['session.store']->get('errors');
        if ($errors) {
            $this->assertSame([], $errors, 'There were errors...');
        }
    }

    public function assertViewResponse($viewName = null)
    {
        if (! isset($this->response->original) || ! $this->response->original instanceof View) {
            PHPUnit::assertTrue(false, 'The response was not a view.');
            return;
        }

        if ($viewName !== null) {
            PHPUnit::assertEquals($viewName, $this->response->original->name(), 'Failed asserting the view responded.');
        }
    }

    public function assertSessionDoesntHave($key)
    {
        if (is_array($key)) {
            return $this->assertSessionDoesntHaveAll($key);
        }

        PHPUnit::assertFalse($this->app['session.store']->has($key), "Session contains key: $key");
    }

    public function assertSessionDoesntHaveAll($keys)
    {
        return ! $this->assertSessionHasAll($keys);
    }

    public function setPreviousUrl($url)
    {
        $this->app['session.store']->setPreviousUrl($url);

        return $this;
    }
}
