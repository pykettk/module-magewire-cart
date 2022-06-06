<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\MagewireCart\Service;

use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Directory\Model\ResourceModel\Country\Collection as CountryCollection;
use Magento\Framework\Exception\NoSuchEntityException;

class Shipping
{
    /** @var CountryInformationAcquirerInterface */
    private CountryInformationAcquirerInterface $countryInfoAcquirer;

    /** @var CountryCollection */
    private CountryCollection $countryCollection;

    /**
     * @param CountryInformationAcquirerInterface $countryInfoAcquirer
     * @param CountryCollection $countryCollection
     */
    public function __construct(
        CountryInformationAcquirerInterface $countryInfoAcquirer,
        CountryCollection $countryCollection
    ) {
        $this->countryInfoAcquirer = $countryInfoAcquirer;
        $this->countryCollection = $countryCollection;
    }

    /**
     * @return array
     */
    public function getCountries(): array
    {
        return $this->countryCollection->loadData()->setForegroundCountries('')->toOptionArray(false);
    }

    /**
     * Get all regions from a given country code.
     *
     * @param string $countryCode
     * @return array
     */
    public function getRegions(string $countryCode = ''): array
    {
        $regions = [];

        if (!$countryCode) {
            return $regions;
        }

        try {
            $availableRegions = $this->countryInfoAcquirer->getCountryInfo($countryCode)->getAvailableRegions() ?? [];

            foreach ($availableRegions as $region) {
                $regions[$region->getId()] = [
                    'code' => $region->getCode(),
                    'name' => $region->getName(),
                ];
            }

            return $regions;
        } catch (NoSuchEntityException $e) {
            return $regions;
        }
    }
}
