<?php

namespace Modules\Symbol\Exchanges;

interface ExchangeInterface
{
    public function symbols(): array;
    public function isSymbolOnline(string $symbol): bool;
    public function orderBook(string $symbol): array;
    public function sendOrder(string $symbol,float $lot, bool $isSell): array;
}
