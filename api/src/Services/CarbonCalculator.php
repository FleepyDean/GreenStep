<?php
declare(strict_types=1);

namespace App\Services;

/**
 * CarbonCalculator Service
 * 
 * Calculates carbon footprint based on configurable emission factors
 * per activity type (Transport, Diet, Energy, Recycling)
 */
class CarbonCalculator
{
    /**
     * Calculate carbon footprint for an activity
     * 
     * @param float $amount Amount of activity (km, meals, kWh, kg)
     * @param float $emissionFactor kg CO2 per unit (can be negative for recycling)
     * @return float Calculated carbon footprint in kg CO2
     */
    public static function calculate(float $amount, float $emissionFactor): float
    {
        return round($amount * $emissionFactor, 4);
    }

    /**
     * Get emission factors for all activity types
     * This is stored in database, but can be retrieved here if needed
     * 
     * @return array Emission factors by category and name
     */
    public static function getEmissionFactors(): array
    {
        return [
            'Transport' => [
                'Car (Petrol)' => 0.1920,
                'Bus / Train travel' => 0.0890,
                'Bicycle commute' => 0.0000,
                'Motorcycle' => 0.1030,
                'Car (Electric)' => 0.0530,
            ],
            'Diet' => [
                'Red Meat Meal' => 6.6100,
                'Vegetarian Meal' => 1.7700,
                'Vegan Meal' => 1.0500,
                'Chicken Meal' => 1.3700,
                'Fish Meal' => 1.4300,
            ],
            'Energy' => [
                'Electricity Usage' => 0.4750,
                'Air Conditioner usage' => 0.8500,
                'Natural Gas' => 0.1850,
            ],
            'Recycling' => [
                'Paper Recycling' => -0.1700,
                'Plastic Recycling' => -0.4500,
                'Glass Recycling' => -0.3000,
                'Aluminum Recycling' => -9.1300,
            ]
        ];
    }

    /**
     * Calculate total carbon footprint for a set of activities
     * 
     * @param array $activities Array of activities with amount and emission_factor
     * @return float Total carbon footprint
     */
    public static function calculateTotal(array $activities): float
    {
        $total = 0.0;
        
        foreach ($activities as $activity) {
            $amount = $activity['amount'] ?? 0;
            $factor = $activity['emission_factor'] ?? 0;
            $total += self::calculate($amount, $factor);
        }
        
        return round($total, 4);
    }

    /**
     * Get category breakdown for a set of activities
     * 
     * @param array $activities Array of activities
     * @return array Category-wise carbon footprint
     */
    public static function getCategoryBreakdown(array $activities): array
    {
        $breakdown = [];
        
        foreach ($activities as $activity) {
            $category = $activity['category'] ?? 'Unknown';
            $amount = $activity['amount'] ?? 0;
            $factor = $activity['emission_factor'] ?? 0;
            
            $carbon = self::calculate($amount, $factor);
            
            if (!isset($breakdown[$category])) {
                $breakdown[$category] = 0;
            }
            $breakdown[$category] += $carbon;
        }
        
        // Round all values
        foreach ($breakdown as $key => $value) {
            $breakdown[$key] = round($value, 4);
        }
        
        return $breakdown;
    }
}
