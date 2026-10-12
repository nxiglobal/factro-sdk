<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class PayloadMethodsTest extends TestCase
{
    /**
     * The body format follows the factro API, so the methods building it stay outside the BC promise.
     */
    public function testBodyBuildersAreInternal(): void
    {
        $src = \dirname(__DIR__, 3).'/src';
        $checked = 0;

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src.'/Resource', \FilesystemIterator::SKIP_DOTS)) as $file) {
            \assert($file instanceof \SplFileInfo);
            $class = 'Nxi\\Factro\\'.str_replace('/', '\\', substr($file->getPathname(), \strlen($src) + 1, -4));
            if (!class_exists($class)) {
                continue;
            }

            foreach (['toPayload', 'toMultipart'] as $name) {
                if (!method_exists($class, $name)) {
                    continue;
                }

                self::assertStringContainsString('@internal', (string) new \ReflectionMethod($class, $name)->getDocComment(), $class.'::'.$name);
                ++$checked;
            }
        }

        self::assertGreaterThanOrEqual(36, $checked);
    }
}
