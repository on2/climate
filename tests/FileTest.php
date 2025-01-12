<?php

namespace League\CLImate\Tests;

require_once __DIR__ . '/../vendor/mikey179/vfsstream/src/main/php/org/bovigo/vfs/vfsStream.php';
require_once 'FileGlobalMock.php';

use League\CLImate\Exceptions\RuntimeException;
use League\CLImate\Util\Output;
use org\bovigo\vfs\vfsStream;

class FileTest extends TestBase
{
    protected $file;

    public function setUp()
    {
        $root       = vfsStream::setup();
        $this->file = vfsStream::newFile('log')->at($root);
    }

    protected function getFileMock()
    {
        return \Mockery::mock('League\CLImate\Util\Writer\File', func_get_args())->makePartial();
    }

    /** @test */
    public function it_can_write_to_a_file()
    {
        $file = $this->getFileMock($this->file->url());

        self::$functions->shouldReceive('fopen')
                        ->once()
                        ->with($this->file->url(), 'a')
                        ->andReturn(fopen($this->file->url(), 'a'));

        $output = new Output;
        $output->add('file', $file);
        $output->defaultTo('file');

        $output->write("Oh, you're still here.");

        $this->assertSame("Oh, you're still here.\n", $this->file->getContent());
    }

    /** @test */
    public function it_will_accept_a_resource()
    {
        $resource = fopen($this->file->url(), 'a');
        $file     = $this->getFileMock($resource);
        $output   = new Output;
        $output->add('file', $file);
        $output->defaultTo('file');

        $output->write("Oh, you're still here.");

        $this->assertSame("Oh, you're still here.\n", $this->file->getContent());
    }

    /** @test */
    public function it_can_lock_the_file()
    {
        $resource = fopen($this->file->url(), 'a');
        $file     = $this->getFileMock($resource);

        self::$functions->shouldReceive('flock')
                        ->once()
                        ->with($resource, LOCK_EX);

        self::$functions->shouldReceive('flock')
                        ->once()
                        ->with($resource, LOCK_UN);

        $file->lock();

        $output = new Output;
        $output->add('file', $file);
        $output->defaultTo('file');

        $output->write("Oh, you're still here.");

        $this->assertSame("Oh, you're still here.\n", $this->file->getContent());
    }

    /** @test */
    public function it_can_write_to_a_gzipped_file()
    {
        // $file = $this->getFileMock($this->file->url());

        // self::$functions->shouldReceive('gzopen')
        //                 ->once()
        //                 ->with($this->file->url(), 'a')
        //                 ->andReturn('file resource');

        // self::$functions->shouldReceive('gzwrite')
        //                 ->once()
        //                 ->with('file resource', "Oh, you're still here.");

        // $file->gzipped();

        // $output = new Output;
        // $output->add('file', $file);
        // $output->defaultTo('file');

        // $output->write("Oh, you're still here.");

        // $this->assertSame("Oh, you're still here.\n", $this->file->getContent());
    }

    /** @test */
    public function it_will_yell_when_a_non_writable_resource_is_passed()
    {
        $this->file->chmod(0444);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("is not writable");

        $file   = $this->getFileMock($this->file->url());
        $output = new Output;
        $output->add('file', $file);
        $output->defaultTo('file');

        $output->write("Oh, you're still here.");
    }

    /** @test */
    public function it_will_yell_when_a_non_existent_resource_is_passed()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The resource [something-that-doesnt-exist] is not writable");

        $file   = $this->getFileMock('something-that-doesnt-exist');
        $output = new Output;
        $output->add('file', $file);
        $output->defaultTo('file');

        $output->write("Oh, you're still here.");
    }

    /** @test */
    public function it_will_yell_when_it_failed_to_open_a_resource()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The resource could not be opened");

        $file = $this->getFileMock($this->file->url());

        self::$functions->shouldReceive('fopen')
                        ->once()
                        ->with($this->file->url(), 'a')
                        ->andReturn(false);

        $output = new Output;
        $output->add('file', $file);
        $output->defaultTo('file');

        $output->write("Oh, you're still here.");
    }
}
