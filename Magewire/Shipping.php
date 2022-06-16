<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\MagewireCart\Magewire;

use Element119\MagewireCart\Scope\Config;
use Element119\MagewireCart\Service\Cart as CartService;
use Element119\MagewireCart\Service\Shipping as ShippingService;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magewirephp\Magewire\Component;

class Shipping extends Component
{
    /** @var Config */
    private Config $config;

    /** @var CartService */
    private CartService $cartService;

    /** @var ShippingService */
    private ShippingService $shippingService;

    /** @var ShipmentEstimationInterface */
    private ShipmentEstimationInterface $shipmentEstimator;

    /** Magewire Component Properties */
    public array $countries = [];
    public string $selectedCountryId = '';

    public array $regions = [];
    public string $region = '';
    public string $selectedRegionId = '';

    public string $postcode = '';

    public array $shippingMethods = [];
    public string $selectedShippingMethod = '';

    /**
     * @param Config $config
     * @param CartService $cartService
     * @param ShippingService $shippingService
     * @param ShipmentEstimationInterface $shipmentEstimator
     */
    public function __construct(
        Config $config,
        CartService $cartService,
        ShippingService $shippingService,
        ShipmentEstimationInterface $shipmentEstimator
    ) {
        $this->config = $config;
        $this->cartService = $cartService;
        $this->shippingService = $shippingService;
        $this->shipmentEstimator = $shipmentEstimator;
    }

    /**
     * Component initial state setup.
     *
     * @return void
     */
    public function mount(): void
    {
        $this->countries = $this->shippingService->getCountries();
        $this->selectedCountryId = (string)array_search(
            $this->config->getDefaultCountry(),
            array_column($this->countries, 'value')
        );
        $this->regions = $this->shippingService->getRegions($this->countries[$this->selectedCountryId]['value']);

        $this->collectShippingRates();
    }

    /**
     * Attempt to collect shipping rates when country is selected.
     *
     * @param $value
     * @return mixed
     */
    public function updatedSelectedCountryId($value)
    {
        $this->regions = $this->shippingService->getRegions($this->countries[$value]['value']);
        $this->collectShippingRates();

        return $value;
    }

    /**
     * Attempt to collect shipping rates when region is selected.
     *
     * @param $value
     * @return mixed
     */
    public function updatedSelectedRegionId($value)
    {
        $this->collectShippingRates();

        return $value;
    }

    /**
     * Attempt to collect shipping rates when region is updated.
     *
     * @param $value
     * @return mixed
     */
    public function updatedRegion($value)
    {
        $this->collectShippingRates();

        return $value;
    }

    /**
     * Attempt to collect shipping rates when postcode is updated.
     *
     * @param $value
     * @return mixed
     */
    public function updatedPostcode($value)
    {
        $this->collectShippingRates();

        return $value;
    }

    /**
     * @param string $method
     * @return void
     */
    public function setShippingMethod(string $method): void
    {
        $this->selectedShippingMethod = $method;
        $this->collectShippingRates();
    }

    /**
     * Collect shipping rates based on current address data and update the quote with shipping data.
     *
     * @return void
     */
    public function collectShippingRates(): void
    {
        if (!($quote = $this->cartService->getQuote())
            || !$this->selectedCountryId
            || !($this->postcode || ($this->region || ($this->regions && $this->selectedRegionId)))
        ) {
            return;
        }

        $quote->getShippingAddress()
            ->setCountryId($this->countries[$this->selectedCountryId]['value'])
            ->setRegionId($this->selectedRegionId)
            ->setRegion($this->region)
            ->setPostcode($this->postcode);

        $shippingMethods = $this->shipmentEstimator->estimateByExtendedAddress(
            (int)$quote->getId(),
            $quote->getShippingAddress()
        );

        $this->shippingMethods = [];

        /** @var ShippingMethodInterface $shippingRate */
        foreach ($shippingMethods as $shippingRate) {
            $this->shippingMethods[$shippingRate->getMethodCode()] = [
                'method_title' => $shippingRate->getMethodTitle(),
                'method' => $shippingRate->getMethodCode(),
                'carrier_title' => $shippingRate->getCarrierTitle(),
                'price_incl_tax' => $shippingRate->getPriceInclTax(),
                'price_excl_tax' => $shippingRate->getPriceExclTax(),
            ];

            if ($this->selectedShippingMethod === $shippingRate->getMethodCode()) {
                /** @TODO: Shipping is not being set on the quote */
                $quote->getShippingAddress()->setCollectShippingRates(true);
                $quote->collectTotals();
                $this->cartService->saveQuote($quote);
            }
        }
    }
}
