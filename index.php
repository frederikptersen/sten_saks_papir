<?php
// Filsti til JSON-datafilen
define('DATA_FILE', 'data.json');

// Funktion til at hente data fra JSON-filen
function getData() {
    if (!file_exists(DATA_FILE)) {
        // Opret grundlæggende data, hvis filen ikke eksisterer
        $data = ['players' => [], 'matches' => []];
        saveData($data); // Gem grundstrukturen i data.json
    } else {
        // Læs data fra filen
        $data = json_decode(file_get_contents(DATA_FILE), true);
        
        // Hvis filen er tom eller ikke korrekt struktureret, initialiserer vi den
        if (!is_array($data)) {
            $data = ['players' => [], 'matches' => []];
            saveData($data);
        }
    }
    return $data;
}

// Funktion til at gemme data i JSON-filen
function saveData($data) {
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

// Hent data i starten af scriptet, så det er tilgængeligt for hele HTML'en
$data = getData();

// Håndter indsendelse af "Tilføj Deltager"
if (isset($_POST['add_player'])) {
    $new_player = htmlspecialchars($_POST['player_name']);
    $exists = false;

    // Tjek om spilleren allerede findes
    foreach ($data['players'] as $player) {
        if ($player['name'] === $new_player) {
            $exists = true;
            break;
        }
    }

    // Tilføj ny deltager, hvis navnet ikke findes
    if (!$exists) {
        $data['players'][] = ['name' => $new_player, 'points' => 0, 'wins' => 0, 'losses' => 0];
        saveData($data);
    }

    // Omdiriger for at undgå genindsendelse ved opdatering
    header("Location: index.php");
    exit();
}

// Håndter indsendelse af "Registrer Kamp"
if (isset($_POST['register_match'])) {
    $winner = $_POST['winner'];
    $loser = $_POST['loser'];
    $score = htmlspecialchars($_POST['score']);

    // Split score på "-" for at få point til vinder og taber
    list($winnerPoints, $loserPoints) = explode('-', $score);

    // Find og opdater vinder og taber i $data
    foreach ($data['players'] as &$player) {
        if ($player['name'] === $winner) {
            $player['points'] += (int)$winnerPoints;
            $player['wins'] += 1;
        } elseif ($player['name'] === $loser) {
            $player['points'] += (int)$loserPoints;
            $player['losses'] += 1;
        }
    }

    // Tilføj kamp til matches
    $data['matches'][] = [
        'timestamp' => date('Y-m-d H:i:s'),
        'winner' => $winner,
        'loser' => $loser,
        'score' => $score
    ];

    // Gem de opdaterede data i JSON-filen
    saveData($data);

    // Omdiriger for at undgå genindsendelse ved opdatering
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Sten, saks & papir</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

    <!-- Tilføj Deltager Sektion -->
    <section class="section">
        <h2>Tilføj Deltager</h2>
        <form method="POST" action="">
            <label for="player_name">Deltagerens Navn:</label>
            <input type="text" id="player_name" name="player_name" required>
            <input type="submit" name="add_player" value="Tilføj Deltager">
            <div class="error" id="addPlayerError"></div>
        </form>
    </section>

    <!-- Registrer Kamp Sektion -->
    <section class="section">
        <h2>Registrer Kamp</h2>
        <form method="POST" action="">
            <label for="winner">Vinder:</label>
            <select id="winner" name="winner" required>
                <?php foreach ($data['players'] as $player) echo "<option value=\"{$player['name']}\">{$player['name']}</option>"; ?>
            </select>
            <label for="loser">Taber:</label>
            <select id="loser" name="loser" required>
                <?php foreach ($data['players'] as $player) echo "<option value=\"{$player['name']}\">{$player['name']}</option>"; ?>
            </select>
            <label for="score">Score:</label>
            <input type="text" id="score" name="score" placeholder="f.eks. 2-1" required>
            <input type="submit" name="register_match" value="Registrer Kamp">
            <div class="error" id="registerMatchError"></div>
        </form>
    </section>

    <!-- Rangliste Sektion -->
    <section class="section">
        <h2>Rangliste</h2>
        <button onclick="updateRanklist()">Opdater</button>
        <table class="table">
            <thead>
                <tr>
                    <th>Navn</th>
                    <th>Points</th>
                    <th>Win%</th>
                </tr>
            </thead>
            <tbody id="ranklist">
                <?php
                foreach ($data['players'] as $player) {
                    $totalGames = $player['wins'] + $player['losses'];
                    $winPercentage = $totalGames > 0 ? round(($player['wins'] / $totalGames) * 100, 2) : 0;
                    echo "<tr>
                            <td>{$player['name']}</td>
                            <td>{$player['points']}</td>
                            <td>{$winPercentage}%</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </section>

    <!-- Seneste Kampe Sektion -->
    <section class="section">
        <h2>Seneste Kampe</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Tidspunkt</th>
                    <th>Vinder</th>
                    <th>Score</th>
                    <th>Taber</th>
                </tr>
            </thead>
            <tbody id="matches">
                <?php
                $matches = array_slice(array_reverse($data['matches']), 0, 10);
                foreach ($matches as $match) {
                    echo "<tr>
                            <td>{$match['timestamp']}</td>
                            <td><strong>{$match['winner']}</strong></td>
                            <td>{$match['score']}</td>
                            <td>{$match['loser']}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </section>

</div>

<!-- Toast Notification -->
<div id="toast" class="toast">Ranklist opdateret!</div>

<script src="script.js"></script>
</body>
</html>
