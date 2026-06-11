<?php

namespace DTO;

class FeaturePopularityDTO {
    private string $featureName;
    private string $iconName;
    private int $totalBookings;

    /**
     * Constructs a new FeaturePopularityDTO instance.
     *
     * @param string $featureName The name of the feature.
     * @param string $iconName The icon name associated with the feature.
     * @param int $totalBookings The total number of bookings for desks with this feature.
     */
    public function __construct(string $featureName, string $iconName, int $totalBookings) {
        $this->featureName = $featureName;
        $this->iconName = $iconName;
        $this->totalBookings = $totalBookings;
    }

    /**
     * Gets the feature name.
     *
     * @return string The feature name.
     */
    public function getFeatureName(): string { return $this->featureName; }

    /**
     * Gets the icon name.
     *
     * @return string The icon name.
     */
    public function getIconName(): string { return $this->iconName; }

    /**
     * Gets the total number of bookings.
     *
     * @return int The total number of bookings.
     */
    public function getTotalBookings(): int { return $this->totalBookings; }
}