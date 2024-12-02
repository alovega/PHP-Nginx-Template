<?php
include './db.php';

function getCurrentVersion() {
    return $_GET['version'] ?? $_POST['version'] ?? null;
}

function getUserIP() {
    return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
}

$user_ip = getUserIP();

//  (This part remains unchanged)
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM licenses WHERE ip = ? AND software_id IN (1, 13)");
$stmt->bind_param("s", $user_ip);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();


function findNearestVersion($conn, $requestedVersion = null) {
    $allVersions = [];
    $result = $conn->query("SELECT cpversion FROM cpanel_versions ORDER BY cpversion ASC"); // Order is important
    while ($row = $result->fetch_assoc()) {
        $allVersions[] = $row['cpversion'];
    }

    if (empty($requestedVersion)) {
        return end($allVersions); // Return the highest version if no request
    }

    if (in_array($requestedVersion, $allVersions)) {
        $currentIndex = array_search($requestedVersion, $allVersions);
        if ($currentIndex < count($allVersions) - 1) {
            // Newer version exists
            $newerVersion = $allVersions[$currentIndex + 1];
            return [
                'version' => $newerVersion,
                'settings' => 'manual' // RPMUP, SARULESUP, UPDATES => manual
            ];
        } else {
            // Requested version is the latest
            return [
                'version' => $requestedVersion,
                'settings' => 'never' // RPMUP, SARULESUP, UPDATES => never
            ];
        }
    } else {
        // Requested version doesn't exist
        // Find the highest version LESS THAN OR EQUAL to the requested version
        $highestExisting = null;
         foreach ($allVersions as $version) {
             if (version_compare($version, $requestedVersion) <= 0) {
                 $highestExisting = $version;
             } else {
                 break; // Stop once we find a higher version.
             }
         }

        return $highestExisting ? [
            'version' => $highestExisting,
             'settings' => 'manual'
         ] : [
             'version' => end($allVersions), // Fallback to highest if nothing below requested is found
             'settings' => 'manual'
         ];

    }
}



$requestedVersion = getCurrentVersion();


$versionInfo = findNearestVersion($conn, $requestedVersion);
$selectedVersion = $versionInfo['version'];
$settings = $versionInfo['settings'];


if ($selectedVersion) {
    $config = "CPANEL=$selectedVersion\nRPMUP=$settings\nSARULESUP=$settings\nSTAGING_DIR=/usr/local/cpanel\nUPDATES=$settings";
    echo $config;
} else {
    echo "ERROR: No cPanel versions found in the database.";
}


$conn->close();