<?php

namespace DTO;

use Models\User;

class UserAttendanceDTO {
    private User $user;
    private int $checkIns;
    private int $reliabilityScore;

    public function __construct(User $user, int $checkIns, int $reliabilityScore) {
        $this->user = $user;
        $this->checkIns = $checkIns;
        $this->reliabilityScore = $reliabilityScore;
    }

    public function getUser(): User { return $this->user; }
    public function getCheckIns(): int { return $this->checkIns; }
    public function getReliabilityScore(): int { return $this->reliabilityScore; }
}