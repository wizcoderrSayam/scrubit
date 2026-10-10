<?php

namespace Tests\Unit\Support;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @return array<string, array{string, int}>
     */
    public static function validAmounts(): array
    {
        return [
            'whole dollars' => ['25', 2500],
            'one decimal place' => ['25.5', 2550],
            'two decimal places' => ['25.50', 2550],
            'cents only' => ['0.07', 7],
            'surrounding whitespace' => [' 19.99 ', 1999],
        ];
    }

    #[DataProvider('validAmounts')]
    public function test_parses_decimal_strings_into_cents(string $input, int $expectedCents): void
    {
        $this->assertSame($expectedCents, Money::parse($input));
    }

    #[TestWith(['25.555'])]
    #[TestWith(['-5'])]
    #[TestWith(['1e3'])]
    #[TestWith(['abc'])]
    #[TestWith([''])]
    public function test_rejects_malformed_amounts(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::parse($input);
    }

    public function test_formats_cents_with_currency_symbol_and_thousands_separator(): void
    {
        $this->assertSame('$2,500.05', Money::format(250005));
        $this->assertSame('-$1.50', Money::format(-150));
        $this->assertSame('EUR 3.00', Money::format(300, 'EUR'));
    }

    public function test_compact_format_omits_zero_cents_only(): void
    {
        $this->assertSame('$25', Money::formatCompact(2500));
        $this->assertSame('$25.50', Money::formatCompact(2550));
    }

    public function test_renders_cents_as_plain_decimal_for_form_fields(): void
    {
        $this->assertSame('25.05', Money::toDecimal(2505));
        $this->assertSame('0.00', Money::toDecimal(0));
    }
}
