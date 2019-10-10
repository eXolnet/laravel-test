<?php namespace Exolnet\Test;

use Faker\Factory as FakerFactory;
use Mockery as m;
use PHPUnit\Framework\TestCase as TestCase;

class BaseTestCase extends TestCase
{
	protected $faker;

	public function setUp()
	{
		$this->faker = FakerFactory::create();
	}

	public function tearDown()
	{
		m::close();
	}
}
