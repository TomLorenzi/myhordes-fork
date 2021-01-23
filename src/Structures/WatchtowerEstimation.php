<?php

namespace App\Structures;

class WatchtowerEstimation {
    private int $min;
    private int $max;
    private int $future;
    private float $estimation;

    /**
     * @return float
     */
    public function getEstimation(): float
    {
        return $this->estimation;
    }

    /**
     * @param float $estimation
     */
    public function setEstimation(float $estimation): void
    {
        $this->estimation = $estimation;
    }

    /**
     * @return int
     */
    public function getMin(): int
    {
        return $this->min;
    }

    /**
     * @param int $min
     */
    public function setMin(int $min): void
    {
        $this->min = $min;
    }

    /**
     * @return int
     */
    public function getMax(): int
    {
        return $this->max;
    }

    /**
     * @param int $max
     */
    public function setMax(int $max): void
    {
        $this->max = $max;
    }

    /**
     * @return int
     */
    public function getFuture(): int
    {
        return $this->future;
    }

    /**
     * @param int $future
     */
    public function setFuture(int $future): void
    {
        $this->future = $future;
    }


}