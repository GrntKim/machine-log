<?php
    declare(strict_types=1);
    $dbPath = dirname(__DIR__) . "/data/machine.sqlite";

    if (!is_dir(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0775, true);
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("
        CREATE TABLE IF NOT EXISTS visits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT,
            user_agent TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $stmt = $db->prepare("
        INSERT INTO visits (ip_address, user_agent)
        VALUES (:ip_address, :user_agent)
    ");
    $stmt->execute([
        ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);

    $visitCount = (int) $db->query("SELECT COUNT(*) FROM visits")->fetchColumn();

    function e($value): string {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    function formatBytes(float $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;
    
        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }
    
        return round($bytes, 1) . ' ' . $units[$index];
    }

    $serverTime = date('Y-m-d H:i:s T');
    $hostname = gethostname() ?: 'unknown';
    $uptime = trim(shell_exec('uptime -p') ?: 'unknown');
    $memory = trim(shell_exec("free -h | awk '/Mem:/ {print $3 \" / \" $2}'") ?: 'unknown');
    $totalDisk = disk_total_space('/');
    $freeDisk = disk_free_space('/');
    
    if ($totalDisk !== false && $freeDisk !== false) {
        $usedDisk = $totalDisk - $freeDisk;
        $usedPercent = $totalDisk > 0 ? round(($usedDisk / $totalDisk) * 100) : 0;
        $disk = formatBytes($usedDisk) . ' / ' . formatBytes($totalDisk) . " ({$usedPercent}%)";
    } else {
        $disk = 'unknown';
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($hostname) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="shell">
        <header class="hero">
            <p class="eyebrow">personal machine</p>
            <h1><?= e($hostname) ?> is online</h1>
            <p class="muted">A tiny page served from my own VPS.</p>
        </header>

        <section class="panel">
            <dl class="stats">
                <div>
                    <dt>Server Time</dt>
                    <dd><?= e($serverTime) ?></dd>
                </div>
                <div>
                    <dt>Uptime</dt>
                    <dd><?= e($uptime) ?></dd>
                </div>
                <div>
                    <dt>Memory</dt>
                    <dd><?= e($memory) ?></dd>
                </div>
                <div>
                    <dt>Disk</dt>
                    <dd><?= e($disk) ?></dd>
                </div>
                <div>
                    <dt>Visits</dt>
                    <dd><?= e($visitCount) ?></dd>
                </div>
            </dl>
        </section>

        <section class="panel">
            <h2>Signal</h2>
            <p id="signal" class="signal">Waiting for a signal...</p>
            <button id="ping-button" type="button">Ping machine</button>
        </section>
    </main>
    <script src="app.js"></script>
</body>
</html>
