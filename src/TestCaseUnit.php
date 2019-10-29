<?php

namespace Exolnet\Test;

use Mockery;
use PHPUnit\Framework\TestCase;

abstract class TestCaseUnit extends TestCase
{
    /**
     * This method is called after each test.
     */
    public function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
