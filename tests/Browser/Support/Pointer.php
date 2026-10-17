<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Browser\Support;

use Closure;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Playwright\Client;
use Pest\Browser\Playwright\Page;
use ReflectionProperty;
use RuntimeException;

final class Pointer
{
    private const STEPS = 25;

    private const STEP_DELAY_MS = 16;

    private const SETTLE_MS = 400;

    /** @var array{x: float, y: float}|null */
    private ?array $position = null;

    private function __construct(
        private readonly AwaitableWebpage $webpage,
        private readonly Page $page,
    ) {}

    public static function on(AwaitableWebpage $webpage): self
    {
        return new self($webpage, PageHandle::of($webpage));
    }

    /** @return array{x: float, y: float} */
    public function pointIn(string $selector, float $xFraction = 0.5, float $yFraction = 0.5): array
    {
        $selectorJson = json_encode($selector, JSON_THROW_ON_ERROR);

        $point = $this->webpage->script(<<<JS
            (() => {
                const box = document.querySelector({$selectorJson}).getBoundingClientRect();

                return { x: box.left + box.width * {$xFraction}, y: box.top + box.height * {$yFraction} };
            })()
        JS);

        return ['x' => (float) $point['x'], 'y' => (float) $point['y']];
    }

    public function press(string $selector): self
    {
        $point = $this->pointIn($selector);

        $this->send('mouseMove', $point);
        $this->send('mouseDown', ['button' => 'left', 'clickCount' => 1]);

        return $this;
    }

    public function glideTo(string $selector, float $xFraction = 0.5, float $yFraction = 0.5): self
    {
        $target = $this->pointIn($selector, $xFraction, $yFraction);
        $from = $this->position ?? $target;

        for ($step = 1; $step <= self::STEPS; $step++) {
            $this->send('mouseMove', [
                'x' => $from['x'] + ($target['x'] - $from['x']) * $step / self::STEPS,
                'y' => $from['y'] + ($target['y'] - $from['y']) * $step / self::STEPS,
            ]);
            $this->wait(self::STEP_DELAY_MS);
        }

        return $this->settle();
    }

    public function release(): self
    {
        $this->send('mouseUp', ['button' => 'left', 'clickCount' => 1]);

        return $this;
    }

    public function click(string $selector): self
    {
        return $this->press($selector)->release();
    }

    public function drag(string $from, string $to, float $yFraction = 0.5): self
    {
        return $this->press($from)->glideTo($to, yFraction: $yFraction)->release();
    }

    public function settle(): self
    {
        return $this->wait(self::SETTLE_MS);
    }

    public function wait(int $milliseconds): self
    {
        $this->webpage->script("new Promise((resolve) => setTimeout(resolve, {$milliseconds}))");

        return $this;
    }

    public function until(Closure $condition, int $timeoutMilliseconds = 8000): void
    {
        for ($waited = 0; $waited < $timeoutMilliseconds; $waited += 100) {
            if ($condition()) {
                return;
            }

            $this->wait(100);
        }

        throw new RuntimeException('Condition not met within ' . $timeoutMilliseconds . 'ms.');
    }

    /** @param  array<string, mixed>  $params */
    private function send(string $method, array $params): void
    {
        if (isset($params['x'], $params['y'])) {
            $this->position = ['x' => $params['x'], 'y' => $params['y']];
        }

        iterator_to_array(Client::instance()->execute($this->guid(), $method, $params));
    }

    private function guid(): string
    {
        return (new ReflectionProperty(Page::class, 'guid'))->getValue($this->page);
    }
}
