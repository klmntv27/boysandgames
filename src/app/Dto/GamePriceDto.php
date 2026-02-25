<?php

namespace App\Dto;

class GamePriceDto
{
    public function __construct(
        public float $initialPrice,
        public float $discountPrice,
        public int $discountPercent,
    ) {
    }

    public function toArray(): array
    {
        return [
            'initial_price' => $this->initialPrice,
            'final_price' => $this->discountPrice,
            'discount_percent' => $this->discountPercent,
        ];
    }
}
