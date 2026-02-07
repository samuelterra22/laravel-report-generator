<?php

namespace SamuelTerra22\ReportGenerator\Tests\Unit;

use PHPUnit\Framework\TestCase;

class LumenHelpersTest extends TestCase
{
    public function test_lumen_file_exists()
    {
        $path = __DIR__ . '/../../src/Lumen.php';
        $this->assertFileExists($path);
    }

    public function test_lumen_file_defines_config_path_function()
    {
        $content = file_get_contents(__DIR__ . '/../../src/Lumen.php');
        $this->assertStringContainsString('function config_path', $content);
    }

    public function test_lumen_file_defines_public_path_function()
    {
        $content = file_get_contents(__DIR__ . '/../../src/Lumen.php');
        $this->assertStringContainsString('function public_path', $content);
    }

    public function test_lumen_file_checks_function_exists()
    {
        $content = file_get_contents(__DIR__ . '/../../src/Lumen.php');
        $this->assertStringContainsString("function_exists('config_path')", $content);
        $this->assertStringContainsString("function_exists('public_path')", $content);
    }
}
