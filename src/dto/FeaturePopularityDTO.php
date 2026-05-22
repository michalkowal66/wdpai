<?php

namespace DTO;

class FeaturePopularityDTO {
    private string $featureName;
    private string $iconName;
    private int $totalBookings;

    public function __construct(string $featureName, string $iconName, int $totalBookings) {
        $this->featureName = $featureName;
        $this->iconName = $iconName;
        $this->totalBookings = $totalBookings;
    }

    public function getFeatureName(): string { return $this->featureName; }
    public function getIconName(): string { return $this->iconName; }
    public function getTotalBookings(): int { return $this->totalBookings; }
}