<?php
require_once "Spaceship.php";

class Fighter extends Spaceship
{
    // Number of canonballs the fighter has
    public int $canonballs;

    // Constructor to initialize the fighter with ammo, fuel, hitPoints, and canonballs
    public function __construct(
        $ammo = 100,
        $fuel = 100,
        $hitPoints = 100,
        $canonballs = 10
    ) {
        parent::__construct($ammo, $fuel, $hitPoints);
        $this->canonballs = $canonballs;
    }

    // Override the shoot method to shoot ammo and return the damage dealt
    public function shoot(): int
    {
        $shot = 10;
        $damage = 10;

        if ($this->ammo - $shot >= 0) {
            $this->ammo -= $shot;
            return ($shot * $damage);
        } else {
            return 0;
        }
    }

    // Method to blast a canonball and return the damage dealt
    public function blast(): int
    {
        $shot = 1;
        $damage = 100;

        if ($this->canonballs > 0) {
            $this->canonballs--;
            return ($shot * $damage);
        } else {
            return 0;
        }
    }

    // Get the number of canonballs
    public function getCanonballs(): int
    {
        return $this->canonballs;
    }

    // Set the number of canonballs
    public function setCanonballs(int $canonballs)
    {
        $this->canonballs = $canonballs;
    }
}
