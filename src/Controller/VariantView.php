<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Controller;

class VariantView
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $code;

    /**
     * @var string
     */
    public $name;

    /**
     * @var PriceView
     */
    public $price;

    /**
     * @var int
     */
    public $stock;

    /**
     * @var int
     */
    public $isTracked;

    /**
     * @var ImageView[]
     */
    public $images = [];
}
