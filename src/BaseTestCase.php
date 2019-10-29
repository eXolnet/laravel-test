<?php

namespace Exolnet\Test;

use Faker\Factory as FakerFactory;
use Mockery;
use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    /**
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * This method is called before each test.
     */
    public function setUp(): void
    {
        $this->faker = FakerFactory::create();
    }

    /**
     * This method is called after each test.
     */
    public function tearDown(): void
    {
        Mockery::close();
    }
}
