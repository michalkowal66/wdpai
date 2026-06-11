<?php

namespace DTO;

use Models\User;

class UserAttendanceDTO {
    private User $user;
    private int $checkIns;
    private int $reliabilityScore;

    /**
     * Constructs a new UserAttendanceDTO instance.
     *
     * @param User $user The user entity.
     * @param int $checkIns The total number of check-ins.
     * @param int $reliabilityScore The calculated reliability score of the user.
     */
    public function __construct(User $user, int $checkIns, int $reliabilityScore) {
        $this->user = $user;
        $this->checkIns = $checkIns;
        $this->reliabilityScore = $reliabilityScore;
    }

    /**
     * Gets the user entity.
     *
     * @return User The user entity.
     */
    public function getUser(): User { return $this->user; }

    /**
     * Gets the number of check-ins.
     *
     * @return int The number of check-ins.
     */
    public function getCheckIns(): int { return $this->checkIns; }

    /**
     * Gets the reliability score.
     *
     * @return int The reliability score.
     */
    public function getReliabilityScore(): int { return $this->reliabilityScore; }
}