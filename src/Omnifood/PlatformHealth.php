<?php

namespace Base\Restaurant\Omnifood;

use Psr\Cache\CacheItemPoolInterface;

/**
 * How each platform's connection stands, for the pass: configured (every key
 * its factory needs is set - in the configuration or typed in the back
 * office), not configured (a key missing: nothing is called, the webhook
 * answers 404), or in error (the last call or webhook failed - what the
 * platform said, and when - until one succeeds). The failures are kept in
 * the cache: a screen's indication, not a record.
 */
class PlatformHealth
{
    public const CONFIGURED = 'configured';
    public const UNCONFIGURED = 'unconfigured';
    public const ERROR = 'error';

    /** A failure is forgotten after this many seconds, if nothing succeeds before */
    public const FORGET = 86400;

    public function __construct(
        private readonly Platforms $platforms,
        private readonly ?CacheItemPoolInterface $cache = null,
    ) {
    }

    /**
     * Every platform the site declares, with its state.
     *
     * @return array<string, array{name: string, factory: ?string, status: string, missing: list<string>, error: ?string, since: ?\DateTimeImmutable}>
     */
    public function all(): array
    {
        $states = [];
        foreach ($this->platforms->names() as $name) {
            $states[$name] = $this->of($name);
        }

        return $states;
    }

    /** @return array{name: string, factory: ?string, status: string, missing: list<string>, error: ?string, since: ?\DateTimeImmutable} */
    public function of(string $name): array
    {
        $missing = $this->platforms->missing($name);
        $failure = $this->failure($name);

        return [
            'name' => $name,
            'factory' => $this->platforms->factoryOf($name),
            'status' => $missing ? self::UNCONFIGURED : ($failure ? self::ERROR : self::CONFIGURED),
            'missing' => $missing,
            'error' => $failure['message'] ?? null,
            'since' => isset($failure['at']) ? new \DateTimeImmutable('@'.$failure['at']) : null,
        ];
    }

    public function failed(string $name, string $message): void
    {
        if (!$this->cache) {
            return;
        }
        $item = $this->cache->getItem($this->key($name));
        $item->set(['message' => mb_substr($message, 0, 300), 'at' => time()])->expiresAfter(self::FORGET);
        $this->cache->save($item);
    }

    public function succeeded(string $name): void
    {
        $this->cache?->deleteItem($this->key($name));
    }

    /** @return array{message: string, at: int}|null */
    private function failure(string $name): ?array
    {
        if (!$this->cache) {
            return null;
        }
        $item = $this->cache->getItem($this->key($name));

        return $item->isHit() && \is_array($item->get()) ? $item->get() : null;
    }

    private function key(string $name): string
    {
        return 'restaurant.platform.'.preg_replace('/[^A-Za-z0-9_]/', '_', $name);
    }
}
