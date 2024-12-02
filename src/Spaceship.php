<?php

class Spaceship
{
    // Properties
    public bool $isAlive; // Indicates if the spaceship is still operational
    public int $fuel; // Amount of fuel the spaceship has
    public int $hitPoints; // Health points of the spaceship
    public int $ammo; // Ammunition available for shooting

    // Constructor to initialize the spaceship with default or provided values
    public function __construct(
        $ammo = 100,
        $fuel = 100,
        $hitPoints = 100
    ) {
        $this->ammo = $ammo;
        $this->fuel = $fuel;
        $this->hitPoints = $hitPoints;
        $this->isAlive = true;
    }

    // Method to shoot and calculate damage
    public function shoot(): int
    {
        $shot = 5; // Ammunition used per shot
        $damage = 10; // Damage per shot

        if ($this->ammo - $shot >= 0) {
            $this->ammo -= $shot;
            return ($shot * $damage);
        } else {
            return 0; // No ammo left to shoot
        }
    }

    // Method to refuel the spaceship
    public function refuel($amount)
    {
        $this->fuel += $amount;
    }

    // Method to handle the spaceship getting hit and reducing hit points
    public function hit($damage)
    {
        if ($this->hitPoints - $damage > 0) {
            $this->hitPoints -= $damage;
        } else {
            $this->isAlive = false; // Spaceship is destroyed
        }
    }

    // Method to move the spaceship, consuming fuel
    public function move()
    {
        $fuelUsage = 2; // Fuel used per move
        if ($this->fuel - $fuelUsage > 0) {
            $this->fuel -= $fuelUsage;
        } else {
            $this->fuel = 0; // No fuel left to move
        }
    }

    // Save the current state of the spaceship to the session
    public function save(): int
    {
        $_SESSION['spaceship'] = serialize($this);
        return 1; // 1 means success
    }

    // Load the spaceship state from the session
    public static function load(): ?Spaceship
    {
        return isset($_SESSION['spaceship']) ? unserialize($_SESSION['spaceship']) : null;
    }

    // Export the current state of the spaceship as a JSON string
    public function export(): string
    {
        return base64_encode(json_encode($this));
    }

    // Import the spaceship state from a JSON string
    public static function import(string $data): ?Spaceship
    {
        $decoded = json_decode(base64_decode($data), true);
        if ($decoded) {
            $spaceship = new self();
            foreach ($decoded as $key => $value) {
                $spaceship->$key = $value;
            }
            return $spaceship;
        }
        return null;
    }

    // Getters and Setters for properties
    public function getAmmo(): int
    {
        return $this->ammo;
    }

    public function setAmmo(int $ammo)
    {
        $this->ammo = $ammo;
    }

    public function getFuel(): int
    {
        return $this->fuel;
    }

    public function setFuel(int $fuel)
    {
        $this->fuel = $fuel;
    }

    public function getHitPoints(): int
    {
        return $this->hitPoints;
    }

    public function setHitPoints(int $hitPoints)
    {
        $this->hitPoints = $hitPoints;
    }
}

class Fleet
{
    public array $spaceships; // Array to hold spaceships in the fleet

    // Constructor to initialize the fleet
    public function __construct()
    {
        $this->spaceships = [];
    }

    // Method to add a spaceship to the fleet
    public function addSpaceship(Spaceship $spaceship)
    {
        $this->spaceships[] = $spaceship;
    }

    // Method to randomize the fleet with a given number of ships
    public function randomizeFleet(int $numShips)
    {
        for ($i = 0; $i < $numShips; $i++) {
            $type = rand(0, 1) ? 'Spaceship' : 'Fighter';
            if ($type === 'Spaceship') {
                $this->addSpaceship(new Spaceship(rand(50, 150), rand(50, 150), rand(50, 150)));
            } else {
                $this->addSpaceship(new Fighter(rand(50, 150), rand(50, 150), rand(50, 150), rand(5, 20)));
            }
        }
    }

    // Method to simulate a battle between two fleets
    public function battle(Fleet $opponentFleet)
    {
        foreach ($this->spaceships as $ship) {
            foreach ($opponentFleet->spaceships as $opponentShip) {
                while ($ship->isAlive && $opponentShip->isAlive) {
                    $damage = $ship->shoot();
                    $opponentShip->hit($damage);
                    if ($opponentShip->isAlive) {
                        $damage = $opponentShip->shoot();
                        $ship->hit($damage);
                    }
                }
            }
        }
    }

    // Save the current state of the fleet to the session
    public function save(): int
    {
        $_SESSION['fleet'] = serialize($this);
        return 1; // 1 means success
    }

    // Load the fleet state from the session
    public static function load(): ?Fleet
    {
        return isset($_SESSION['fleet']) ? unserialize($_SESSION['fleet']) : null;
    }

    // Export the current state of the fleet as a JSON string
    public function export(): string
    {
        return base64_encode(json_encode($this));
    }

    // Import the fleet state from a JSON string
    public static function import(string $data): ?Fleet
    {
        $decoded = json_decode(base64_decode($data), true);
        if ($decoded) {
            $fleet = new self();
            foreach ($decoded['spaceships'] as $spaceshipData) {
                $fleet->spaceships[] = Spaceship::import(base64_encode(json_encode($spaceshipData)));
            }
            return $fleet;
        }
        return null;
    }

    // Method to get the ranking of spaceships in the fleet based on their score
    public function getRanking(): array
    {
        $ranking = [];
        foreach ($this->spaceships as $ship) {
            $score = ($ship->ammo + $ship->fuel + $ship->hitPoints) / 3;
            $ranking[] = ['ship' => $ship, 'score' => $score];
        }
        usort($ranking, fn($a, $b) => $b['score'] <=> $a['score']);
        return $ranking;
    }
}
