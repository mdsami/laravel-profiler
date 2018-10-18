<?php

namespace JKocik\Laravel\Profiler\Tests\Unit\Services\Timer;

use Mockery;
use Illuminate\Foundation\Application;
use JKocik\Laravel\Profiler\Tests\TestCase;
use JKocik\Laravel\Profiler\Services\Timer\TimerService;
use JKocik\Laravel\Profiler\Services\Timer\TimerException;
use JKocik\Laravel\Profiler\Services\Timer\NullTimerService;

class TimerServiceTest extends TestCase
{
    /** @test */
    function counts_execution_time_in_milliseconds()
    {
        $timer = $this->app->make(TimerService::class);

        $timer->start('testA');
        $timer->finish('testA');

        $timer->start('testB');
        usleep(10 * 1000);
        $timer->finish('testB');

        $timer->start('testC');
        usleep(200 * 1000);
        $timer->finish('testC');

        $this->assertGreaterThanOrEqual(0, $timer->milliseconds('testA'));
        $this->assertGreaterThanOrEqual(10, $timer->milliseconds('testB'));
        $this->assertGreaterThanOrEqual(200, $timer->milliseconds('testC'));
    }

    /** @test */
    function counts_laravel_execution_time()
    {
        $appL = Mockery::mock(Application::class);
        $appL->shouldReceive('environment')->with('testing')->andReturn(false)->once();

        $appT = Mockery::mock(Application::class);
        $appT->shouldReceive('environment')->with('testing')->andReturn(true)->once();

        $timerA1 = new TimerService($appL);
        $timerB1 = new TimerService($appT);
        $timerA2 = new TimerService($appL);
        $timerB2 = new TimerService($appT);
        $millisecondsWithLaravelStartDefined = \microtime(true) * 1000;

        $timerA1->startLaravel();
        $timerA1->finishLaravel();
        $timerB1->startLaravel();
        $timerB1->finishLaravel();

        define('LARAVEL_START', 0);
        $timerA2->startLaravel();
        $timerA2->finishLaravel();
        $timerB2->startLaravel();
        $timerB2->finishLaravel();

        $this->assertLessThan($millisecondsWithLaravelStartDefined, $timerA1->milliseconds('laravel'));
        $this->assertGreaterThanOrEqual($millisecondsWithLaravelStartDefined, $timerA2->milliseconds('laravel'));
        $this->assertLessThan($millisecondsWithLaravelStartDefined, $timerB1->milliseconds('laravel'));
        $this->assertLessThan($millisecondsWithLaravelStartDefined, $timerB2->milliseconds('laravel'));
    }

    /** @test */
    function returns_all_finished_times()
    {
        $timer = $this->app->make(TimerService::class);

        $timer->start('testA');
        $timer->finish('testA');

        $timer->start('testB');
        $timer->finish('testB');

        $timer->start('testC');

        $this->assertArrayHasKey('testA', $timer->all());
        $this->assertArrayHasKey('testB', $timer->all());
        $this->assertArrayNotHasKey('testC', $timer->all());
        $this->assertEquals($timer->milliseconds('testA'), $timer->all()['testA']);
        $this->assertEquals($timer->milliseconds('testB'), $timer->all()['testB']);
    }

    /** @test */
    function returns_negative_value_when_timer_for_specific_label_is_not_completed()
    {
        $timer = $this->app->make(TimerService::class);

        $timer->start('testA');

        $this->assertEquals(-1, $timer->milliseconds('testA'));
    }

    /** @test */
    function returns_empty_values_for_null_timer()
    {
        $timer = new NullTimerService();

        $timer->start('testA');
        $timer->finish('testA');

        $this->assertEquals(0, $timer->milliseconds('testA'));
        $this->assertEquals([], $timer->all());
    }

    /** @test */
    function the_same_timer_can_not_be_started_more_than_once()
    {
        try {
            $timer = $this->app->make(TimerService::class);

            $timer->start('testA');
            $timer->start('testA');
        } catch (TimerException $e) {
            $this->assertTrue(true);
            return;
        }

        $this->fail('TimerException should be thrown');
    }

    /** @test */
    function the_same_timer_can_not_be_finished_more_than_once()
    {
        try {
            $timer = $this->app->make(TimerService::class);

            $timer->start('testA');
            $timer->finish('testA');
            $timer->finish('testA');
        } catch (TimerException $e) {
            $this->assertTrue(true);
            return;
        }

        $this->fail('TimerException should be thrown');
    }

    /** @test */
    function timer_can_not_be_finished_if_is_not_started_before()
    {
        try {
            $timer = $this->app->make(TimerService::class);

            $timer->finish('testA');
        } catch (TimerException $e) {
            $this->assertTrue(true);
            return;
        }

        $this->fail('TimerException should be thrown');
    }
}
