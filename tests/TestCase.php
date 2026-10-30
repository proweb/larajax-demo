<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use October\Rain\Database\Model;

abstract class TestCase extends BaseTestCase
{
    /**
     * Set up the test environment.
     *
     * Rain models register their event methods (beforeSave and the like) once
     * per process, so each test application's new event dispatcher needs them
     * registered again.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Model::flushEventListeners();
    }
}
