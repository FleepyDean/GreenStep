<?php
declare(strict_types=1);

namespace App\Services;

class CarbonDatasetService
{
    public static function getDatasetInfo(): array
    {
        return [
            "name" => "UK Government Greenhouse Gas Conversion Factors",
            "year" => "2025",
            "provider" => "Department for Energy Security and Net Zero",
            "url" => "https://www.gov.uk/government/collections/government-conversion-factors-for-company-reporting"
        ];
    }
}