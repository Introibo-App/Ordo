<?php

declare(strict_types=1);

namespace Directorium\Ordo\Tests;

use DateTimeImmutable;
use Directorium\Ordo\Engine\Core;
use Directorium\Ordo\Rest;
use Directorium\Ordo\Surface\Context;
use PHPUnit\Framework\TestCase;

/**
 * The REST payload builder feeds the day-view modal (Epic #15). It is a pure method —
 * no WordPress runtime — so these tests exercise it directly: the Latin bar label, the
 * feast title, the shared article body, and the navigator window of neighbouring days
 * the modal footer steps through.
 */
final class RestTest extends TestCase
{
    private function rest(): Rest
    {
        return new Rest(new Core());
    }

    public function testPayloadDescribesTheDayAndNavigatorWindow(): void
    {
        $payload = $this->rest()->payload(new DateTimeImmutable('2026-09-03'), Context::forCalendar('sspx'));

        self::assertSame('2026-09-03', $payload['iso']);
        self::assertSame('3 Septembris mmxxvi · Feria V', $payload['barLabel']);
        self::assertSame('S. Pii X Papae Confessoris', $payload['title']);
        self::assertStringContainsString('class="ordo-day"', $payload['html']);
        self::assertStringContainsString('First class · Feast', $payload['html']);

        // Seven days centred on the requested day.
        self::assertCount(7, $payload['nav']);
        self::assertSame('2026-08-31', $payload['nav'][0]['iso']);
        self::assertSame('2026-09-03', $payload['nav'][3]['iso']);
        self::assertSame('2026-09-06', $payload['nav'][6]['iso']);
        self::assertSame('white', $payload['nav'][3]['colour']);
        self::assertSame('THU', $payload['nav'][3]['wd']);
        self::assertSame('3', $payload['nav'][3]['dd']);
    }

    public function testPayloadHtmlListsCommemorations(): void
    {
        $payload = $this->rest()->payload(new DateTimeImmutable('2026-09-16'), Context::forCalendar('universal'));

        self::assertStringContainsString('Ss. Euphemiae Virginis et Martyris', $payload['html']);
    }
}
