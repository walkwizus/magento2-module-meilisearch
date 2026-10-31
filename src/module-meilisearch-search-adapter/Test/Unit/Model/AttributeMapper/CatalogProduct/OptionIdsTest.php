<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchSearchAdapter\Test\Unit\Model\AttributeMapper\CatalogProduct;

use Magento\Eav\Model\Entity\Attribute;
use PHPUnit\Framework\TestCase;
use Walkwizus\MeilisearchSearchAdapter\Model\AttributeMapper\CatalogProduct\OptionIds;
use Walkwizus\MeilisearchSearchAdapter\Model\AttributeProvider\CatalogProduct\OptionIds as OptionIdsProvider;

class OptionIdsTest extends TestCase
{
    public function testMapsOptionIdsPerProduct(): void
    {
        $provider = $this->createMock(OptionIdsProvider::class);
        $provider->method('getAttributes')->willReturn([
            93 => $this->attribute('color'),
            99 => $this->attribute('visibility'),
            141 => $this->attribute('activity'),
        ]);

        $documents = (new OptionIds($provider))->map([
            // a configurable: its children's colours are unioned, its own visibility wins over theirs
            10 => [93 => [11 => '49', 12 => '50', 13 => '49'], 99 => [10 => '4', 11 => '1', 12 => '1'], 141 => [10 => '8,11']],
            20 => [93 => '', 77 => 'not an option attribute'],
        ], 1);

        self::assertSame([10 => ['color_ids' => [49, 50], 'visibility_ids' => [4], 'activity_ids' => [8, 11]]], $documents);
    }

    public function testKeepsOptionValuesThatAreNotIntegers(): void
    {
        $provider = $this->createMock(OptionIdsProvider::class);
        $provider->method('getAttributes')->willReturn([114 => $this->attribute('country_of_manufacture')]);

        $documents = (new OptionIds($provider))->map([1 => [114 => 'US'], 9 => [114 => 'GB']], 1);

        self::assertSame([1 => ['country_of_manufacture_ids' => ['US']], 9 => ['country_of_manufacture_ids' => ['GB']]], $documents);
    }

    /**
     * @param string $code
     * @return Attribute
     */
    private function attribute(string $code): Attribute
    {
        $attribute = $this->createMock(Attribute::class);
        $attribute->method('getAttributeCode')->willReturn($code);

        return $attribute;
    }
}
