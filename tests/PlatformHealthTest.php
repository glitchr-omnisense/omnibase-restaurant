<?php

namespace Base\Restaurant\Tests;

use Base\Restaurant\Omnifood\PlatformHealth;
use Base\Restaurant\Omnifood\Platforms;
use Omnifood\Registry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * How the pass tells a platform's connection: configured when every key its
 * factory needs is set (configured or typed), not configured otherwise -
 * with the keys missing -, in error after a failed call until one succeeds.
 * Needs glitchr/omnifood (skipped without it); no platform is built, no
 * call is made.
 */
class PlatformHealthTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(Registry::class)) {
            self::markTestSkipped('glitchr/omnifood is not installed.');
        }
    }

    private function platforms(array $config): Platforms
    {
        return new Platforms(new Registry([], $config));
    }

    public function testAPlatformWithoutItsKeysIsNotConfiguredAndSaysWhichAreMissing(): void
    {
        $platforms = $this->platforms([
            'thefork' => ['factory' => 'thefork', 'options' => ['client_id' => '', 'client_secret' => null, 'restaurant_id' => 'r-1', 'webhook_token' => 't']],
            'ubereats' => ['factory' => 'ubereats', 'options' => ['client_id' => 'id', 'client_secret' => 'secret', 'store_id' => 'store-1']],
            'deliveroo' => ['factory' => 'deliveroo', 'options' => []],
        ]);
        $health = new PlatformHealth($platforms, new ArrayAdapter());

        self::assertSame(['client_id', 'client_secret'], $platforms->missing('thefork'));
        self::assertFalse($platforms->isConfigured('thefork'));
        self::assertSame([], $platforms->missing('ubereats'));
        self::assertTrue($platforms->isConfigured('ubereats'));
        self::assertSame(['client_id', 'client_secret', 'brand_id', 'site_id', 'webhook_secret'], $platforms->missing('deliveroo'));
        self::assertFalse($platforms->isConfigured('nowhere'), 'a platform the site does not declare');

        $all = $health->all();
        self::assertSame(['thefork', 'ubereats', 'deliveroo'], array_keys($all));
        self::assertSame(PlatformHealth::UNCONFIGURED, $all['thefork']['status']);
        self::assertSame('thefork', $all['thefork']['factory']);
        self::assertSame(PlatformHealth::CONFIGURED, $all['ubereats']['status']);
        self::assertSame(PlatformHealth::UNCONFIGURED, $all['deliveroo']['status']);
    }

    public function testAFailedCallMarksAConfiguredPlatformInErrorUntilOneSucceeds(): void
    {
        $platforms = $this->platforms(['ubereats' => ['factory' => 'ubereats', 'options' => ['client_id' => 'id', 'client_secret' => 'secret', 'store_id' => 'store-1']]]);
        $health = new PlatformHealth($platforms, new ArrayAdapter());

        $health->failed('ubereats', 'HTTP 503 from the platform');
        $state = $health->of('ubereats');
        self::assertSame(PlatformHealth::ERROR, $state['status']);
        self::assertSame('HTTP 503 from the platform', $state['error']);
        self::assertInstanceOf(\DateTimeImmutable::class, $state['since']);

        $health->succeeded('ubereats');
        $state = $health->of('ubereats');
        self::assertSame(PlatformHealth::CONFIGURED, $state['status']);
        self::assertNull($state['error']);
    }

    public function testAnUnconfiguredPlatformStaysUnconfiguredWhateverFails(): void
    {
        $platforms = $this->platforms(['justeat' => ['factory' => 'justeat', 'options' => ['api_key' => 'k']]]);
        $health = new PlatformHealth($platforms, new ArrayAdapter());

        $health->failed('justeat', 'whatever');
        self::assertSame(PlatformHealth::UNCONFIGURED, $health->of('justeat')['status'], 'nothing was called: the keys come first');
        self::assertSame(['restaurant', 'webhook_secret'], $health->of('justeat')['missing']);
    }

    public function testWithoutACacheNothingIsRememberedAndNothingBreaks(): void
    {
        $platforms = $this->platforms(['ubereats' => ['factory' => 'ubereats', 'options' => ['client_id' => 'id', 'client_secret' => 'secret', 'store_id' => 'store-1']]]);
        $health = new PlatformHealth($platforms);

        $health->failed('ubereats', 'lost');
        self::assertSame(PlatformHealth::CONFIGURED, $health->of('ubereats')['status']);
    }
}
