<?php
session_start();

// Define the Spaceship class
class Spaceship
{
    public bool $isAlive;
    public int $fuel;
    public int $hitPoints;
    public int $ammo;

    public function __construct($ammo = 100, $fuel = 100, $hitPoints = 100)
    {
        $this->ammo = $ammo;
        $this->fuel = $fuel;
        $this->hitPoints = $hitPoints;
        $this->isAlive = true;
    }

    public function shoot(): int
    {
        $shot = 5;
        $damage = 10;
        if ($this->ammo - $shot >= 0) {
            $this->ammo -= $shot;
            return ($shot * $damage);
        } else {
            return 0;
        }
    }

    public function refuel($amount)
    {
        $this->fuel += $amount;
    }

    public function hit($damage)
    {
        if ($this->hitPoints - $damage > 0) {
            $this->hitPoints -= $damage;
        } else {
            $this->isAlive = false;
        }
    }

    public function move()
    {
        $fuelUsage = 2;
        if ($this->fuel - $fuelUsage > 0) {
            $this->fuel -= $fuelUsage;
        } else {
            $this->fuel = 0;
        }
    }

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
}

// Define the Fighter class
class Fighter extends Spaceship
{
    public int $canonballs;

    public function __construct($ammo = 100, $fuel = 100, $hitPoints = 100, $canonballs = 10)
    {
        parent::__construct($ammo, $fuel, $hitPoints);
        $this->canonballs = $canonballs;
    }

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

    public function save(): int
    {
        $_SESSION['xSpaceship'] = $this;
        return 1;
    }

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

    public function getCanonballs(): int
    {
        return $this->canonballs;
    }

    public function setCanonballs(int $canonballs)
    {
        $this->canonballs = $canonballs;
    }
}

// Define the Fleet class
class Fleet
{
    public array $ships;

    public function __construct()
    {
        $this->ships = [];
    }

    public function addShip(Spaceship $ship)
    {
        $this->ships[] = $ship;
    }

    public function randomizeFleet(int $numShips)
    {
        for ($i = 0; $i < $numShips; $i++) {
            $this->addShip(new Spaceship(rand(50, 100), rand(50, 100), rand(50, 100)));
        }
    }

    public function battle(Fleet $opponentFleet)
    {
        foreach ($this->ships as $ship) {
            foreach ($opponentFleet->ships as $opponentShip) {
                $damage = $ship->shoot();
                $opponentShip->hit($damage);
            }
        }
    }

    public function save(): int
    {
        $_SESSION['fleet'] = serialize($this);
        return 1; // 1 means success
    }

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
            foreach ($decoded['ships'] as $spaceshipData) {
                $fleet->ships[] = Spaceship::import(base64_encode(json_encode($spaceshipData)));
            }
            return $fleet;
        }
        return null;
    }

    public function getRanking(): array
    {
        usort($this->ships, fn($a, $b) => $b->hitPoints - $a->hitPoints);
        return array_map(fn($ship) => ['score' => $ship->hitPoints, 'ship' => $ship], $this->ships);
    }
}

// Create two fleets
$fleet1 = new Fleet();
$fleet2 = new Fleet();

// Randomize fleets with 5 ships each
$fleet1->randomizeFleet(5);
$fleet2->randomizeFleet(5);

$battleStarted = false;
$battleEnded = false;
$ranking1 = [];
$ranking2 = [];
$battleLog = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start_battle'])) {
        $battleStarted = true;
        foreach ($fleet1->ships as $index1 => $ship) {
            foreach ($fleet2->ships as $index2 => $opponentShip) {
                while ($ship->isAlive && $opponentShip->isAlive) {
                    $damage = $ship->shoot();
                    $opponentShip->hit($damage);
                    $battleLog[] = "
                        <strong>Fleet 1 Ship " . ($index1 + 1) . ":</strong><br>
                        Dealt $damage damage to Fleet 2 Ship " . ($index2 + 1) . "<br>
                        Ammo: {$ship->getAmmo()} (decreased by 5 due to shooting)<br>
                        Fuel: {$ship->getFuel()} (unchanged)<br>
                        Hit Points: {$ship->getHitPoints()} (unchanged)
                    ";
                    if ($opponentShip->isAlive) {
                        $damage = $opponentShip->shoot();
                        $ship->hit($damage);
                        $battleLog[] = "
                            <strong>Fleet 2 Ship " . ($index2 + 1) . ":</strong><br>
                            Dealt $damage damage to Fleet 1 Ship " . ($index1 + 1) . "<br>
                            Ammo: {$opponentShip->getAmmo()} (decreased by 10 due to shooting)<br>
                            Fuel: {$opponentShip->getFuel()} (unchanged)<br>
                            Hit Points: {$opponentShip->getHitPoints()} (unchanged)
                        ";
                    }
                }
            }
        }
        $battleEnded = true;
        $ranking1 = $fleet1->getRanking();
        $ranking2 = $fleet2->getRanking();
    } elseif (isset($_POST['update_fleets'])) {
        // Update fleet properties based on form input
        // ...code to update fleets...
    } elseif (isset($_POST['reset'])) {
        // Reset the page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['save_fleet1'])) {
        // Save the state of fleet1 to the session
        $fleet1->save();
    } elseif (isset($_POST['save_fleet2'])) {
        // Save the state of fleet2 to the session
        $fleet2->save();
    } elseif (isset($_POST['load_fleet1'])) {
        // Load the state of fleet1 from the session
        $fleet1 = Fleet::load() ?? new Fleet();
    } elseif (isset($_POST['load_fleet2'])) {
        // Load the state of fleet2 from the session
        $fleet2 = Fleet::load() ?? new Fleet();
    } elseif (isset($_POST['export_fleet1'])) {
        // Export the state of fleet1
        $exportCode1 = $fleet1->export();
    } elseif (isset($_POST['export_fleet2'])) {
        // Export the state of fleet2
        $exportCode2 = $fleet2->export();
    } elseif (isset($_POST['import_fleet1'])) {
        // Import the state of fleet1
        $fleet1 = Fleet::import($_POST['import_code1']) ?? new Fleet();
    } elseif (isset($_POST['import_fleet2'])) {
        // Import the state of fleet2
        $fleet2 = Fleet::import($_POST['import_code2']) ?? new Fleet();
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Spaceship Control</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Cambria, serif;
            font-size: 18px;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2, h3 {
            color: #333;
        }
        form {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="submit"] {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        ul li {
            background-color: #f9f9f9;
            margin-bottom: 5px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .battle-log {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .battle-log h3 {
            margin-top: 0;
        }
        canvas {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Spaceship Control</h1>
        <?php
        // Create an instance of Spaceship with default values
        $ship1 = new Spaceship();

        // Create an instance of Fighter with custom values
        $ship2 = new Fighter(50, 50, 50);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_fleets'])) {
            // Set properties for ship1
            $ship1->setAmmo((int)$_POST['ship1_ammo']);
            $ship1->setFuel((int)$_POST['ship1_fuel']);
            $ship1->setHitPoints((int)$_POST['ship1_hitPoints']);

            // Set properties for ship2
            $ship2->setCanonballs((int)$_POST['ship2_canonballs']);
        }
        ?>

        <form method="post">
            <h2>Ship1 (Spaceship)</h2>
            <label for="ship1_ammo">Ammo:</label>
            <input type="number" id="ship1_ammo" name="ship1_ammo" value="<?php echo $ship1->getAmmo(); ?>"><br>
            <label for="ship1_fuel">Fuel:</label>
            <input type="number" id="ship1_fuel" name="ship1_fuel" value="<?php echo $ship1->getFuel(); ?>"><br>
            <label for="ship1_hitPoints">Hit Points:</label>
            <input type="number" id="ship1_hitPoints" name="ship1_hitPoints" value="<?php echo $ship1->getHitPoints(); ?>"><br>

            <h2>Ship2 (Fighter)</h2>
            <label for="ship2_canonballs">Canonballs:</label>
            <input type="number" id="ship2_canonballs" name="ship2_canonballs" value="<?php echo $ship2->getCanonballs(); ?>"><br>

            <input type="submit" name="update_fleets" value="Update Fleets">
        </form>

        <form method="post">
            <input type="submit" name="start_battle" value="Start Battle">
        </form>

        <form method="post">
            <input type="submit" name="reset" value="Reset">
        </form>

        <form method="post">
            <!-- Save and Load buttons for Fleet 1 -->
            <input type="submit" name="save_fleet1" value="Save Fleet 1">
            <input type="submit" name="load_fleet1" value="Load Fleet 1">
            <input type="submit" name="export_fleet1" value="Export Fleet 1">
            <input type="text" name="import_code1" placeholder="Import Code for Fleet 1">
            <input type="submit" name="import_fleet1" value="Import Fleet 1">
        </form>

        <form method="post">
            <!-- Save and Load buttons for Fleet 2 -->
            <input type="submit" name="save_fleet2" value="Save Fleet 2">
            <input type="submit" name="load_fleet2" value="Load Fleet 2">
            <input type="submit" name="export_fleet2" value="Export Fleet 2">
            <input type="text" name="import_code2" placeholder="Import Code for Fleet 2">
            <input type="submit" name="import_fleet2" value="Import Fleet 2">
        </form>

        <?php if (isset($exportCode1)): ?>
            <div>
                <h3>Export Code for Fleet 1</h3>
                <textarea readonly><?php echo $exportCode1; ?></textarea>
            </div>
        <?php endif; ?>

        <?php if (isset($exportCode2)): ?>
            <div>
                <h3>Export Code for Fleet 2</h3>
                <textarea readonly><?php echo $exportCode2; ?></textarea>
            </div>
        <?php endif; ?>

        <?php if ($battleEnded): ?>
            <div class="battle-log">
                <h3>Battle Results</h3>
                <h4>Battle Log</h4>
                <ul>
                    <?php foreach ($battleLog as $log): ?>
                        <li><?php echo $log; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <h3>Fleet 1 Ranking</h3>
            <canvas id="fleet1AmmoChart"></canvas>
            <canvas id="fleet1FuelChart"></canvas>
            <canvas id="fleet1HitPointsChart"></canvas>
            <h3>Fleet 2 Ranking</h3>
            <canvas id="fleet2AmmoChart"></canvas>
            <canvas id="fleet2FuelChart"></canvas>
            <canvas id="fleet2HitPointsChart"></canvas>

            <script>
                const fleet1AmmoData = <?php echo json_encode(array_map(fn($r) => $r['ship']->getAmmo(), $ranking1)); ?>;
                const fleet1FuelData = <?php echo json_encode(array_map(fn($r) => $r['ship']->getFuel(), $ranking1)); ?>;
                const fleet1HitPointsData = <?php echo json_encode(array_map(fn($r) => $r['ship']->getHitPoints(), $ranking1)); ?>;
                const fleet2AmmoData = <?php echo json_encode(array_map(fn($r) => $r['ship']->getAmmo(), $ranking2)); ?>;
                const fleet2FuelData = <?php echo json_encode(array_map(fn($r) => $r['ship']->getFuel(), $ranking2)); ?>;
                const fleet2HitPointsData = <?php echo json_encode(array_map(fn($r) => $r['ship']->getHitPoints(), $ranking2)); ?>;

                new Chart(document.getElementById('fleet1AmmoChart'), {
                    type: 'bar',
                    data: {
                        labels: fleet1AmmoData.map((_, i) => `Ship ${i + 1}`),
                        datasets: [{
                            label: 'Fleet 1 Ammo',
                            data: fleet1AmmoData,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                new Chart(document.getElementById('fleet1FuelChart'), {
                    type: 'bar',
                    data: {
                        labels: fleet1FuelData.map((_, i) => `Ship ${i + 1}`),
                        datasets: [{
                            label: 'Fleet 1 Fuel',
                            data: fleet1FuelData,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                new Chart(document.getElementById('fleet1HitPointsChart'), {
                    type: 'bar',
                    data: {
                        labels: fleet1HitPointsData.map((_, i) => `Ship ${i + 1}`),
                        datasets: [{
                            label: 'Fleet 1 Hit Points',
                            data: fleet1HitPointsData,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                new Chart(document.getElementById('fleet2AmmoChart'), {
                    type: 'bar',
                    data: {
                        labels: fleet2AmmoData.map((_, i) => `Ship ${i + 1}`),
                        datasets: [{
                            label: 'Fleet 2 Ammo',
                            data: fleet2AmmoData,
                            backgroundColor: 'rgba(153, 102, 255, 0.2)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                new Chart(document.getElementById('fleet2FuelChart'), {
                    type: 'bar',
                    data: {
                        labels: fleet2FuelData.map((_, i) => `Ship ${i + 1}`),
                        datasets: [{
                            label: 'Fleet 2 Fuel',
                            data: fleet2FuelData,
                            backgroundColor: 'rgba(153, 102, 255, 0.2)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                new Chart(document.getElementById('fleet2HitPointsChart'), {
                    type: 'bar',
                    data: {
                        labels: fleet2HitPointsData.map((_, i) => `Ship ${i + 1}`),
                        datasets: [{
                            label: 'Fleet 2 Hit Points',
                            data: fleet2HitPointsData,
                            backgroundColor: 'rgba(153, 102, 255, 0.2)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>
        <?php endif; ?>
    </div>
</body>

</html> 

