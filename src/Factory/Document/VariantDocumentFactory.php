<?php

declare(strict_types=1);

namespace Sylius\ElasticSearchPlugin\Factory\Document;

use ONGR\ElasticsearchBundle\Collection\Collection;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Sylius\ElasticSearchPlugin\Document\ImageDocument;
use Sylius\ElasticSearchPlugin\Document\VariantDocument;

class VariantDocumentFactory implements VariantDocumentFactoryInterface
{

    /** @var string */
    private $variantDocumentClass;

    /** @var PriceDocumentFactoryInterface */
    private $priceDocumentFactory;

    /** @var ImageDocumentFactoryInterface */
    private $imageDocumentFactory;

    /** @var OptionDocumentFactoryInterface */
    private $optionDocumentFactory;

    public function __construct(
        string $variantDocumentClass,
        PriceDocumentFactoryInterface $priceDocumentFactory,
        ImageDocumentFactoryInterface $imageDocumentFactory,
        OptionDocumentFactoryInterface $optionDocumentFactory
    ) {
        $this->variantDocumentClass = $variantDocumentClass;
        $this->priceDocumentFactory = $priceDocumentFactory;
        $this->imageDocumentFactory = $imageDocumentFactory;
        $this->optionDocumentFactory = $optionDocumentFactory;
    }

    public function create(
        ProductVariantInterface $productVariant,
        ChannelInterface $channel,
        LocaleInterface $locale
    ): VariantDocument {

        $options = [];
        foreach ($productVariant->getOptionValues() as $optionValue) {
            $options[] = $this->optionDocumentFactory->create($optionValue, $locale);
        }

        /** @var ChannelPricingInterface $channelPricing */
        $channelPricing = $this->getChannelPricing($productVariant, $channel);

        $price = $this->priceDocumentFactory->create(
            $channelPricing,
            $channel->getBaseCurrency()
        );


        /** @var ProductVariantTranslationInterface $productVariantTranslation */
        $productVariantTranslation = $productVariant->getTranslation($locale->getCode());

        /** @var VariantDocument $variant */
        $variant = new $this->variantDocumentClass();
        $variant->setId($productVariant->getId());
        $variant->setCode($productVariant->getCode());
        $variant->setName($productVariantTranslation->getName());
        $variant->setPrice($price);
        $variant->setStock($productVariant->getOnHand() - $productVariant->getOnHold());
        $variant->setIsTracked($productVariant->isTracked());
        $variant->setOptions(new Collection($options));
        if ($productVariant->getImages()->count() > 0) {
            /** @var ImageDocument[] $images */
            $images = [];
            foreach ($productVariant->getImages() as $image) {
                $images[] = $this->imageDocumentFactory->create($image);
            }
            $variant->setImages(new Collection($images));
        }

        return $variant;
    }

    /**
     * Get price for the pricing document
     *
     * @param ProductVariantInterface $productVariant
     * @param ChannelInterface $channel
     *
     * @return ChannelPricingInterface|null
     */
    protected function getChannelPricing(
        ProductVariantInterface $productVariant,
        ChannelInterface $channel
    ): ?ChannelPricingInterface
    {
        $channelPricings = $productVariant->getChannelPricings();

        foreach ($channelPricings as $channelPricing) {
            if ($channelPricing->getChannelCode() === $channel->getCode()) {
                return $channelPricing;
            }
        }

        return null;
    }
}
