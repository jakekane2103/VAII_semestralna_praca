<?php

// ---------------------------
// DATABASE CONNECTION
// ---------------------------
$pdo = new PDO(
    "mysql:host=localhost;dbname=vaiicko_db;charset=utf8mb4",
    "vaiicko_user",
    "dtb456",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ---------------------------
// API SOURCES (mix of genres)
// ---------------------------
$queries = [
    "subject:fantasy",
    "subject:science+fiction",
    "subject:horror",
];

$booksNeeded = 40;
$collected = [];

foreach ($queries as $query) {

    $url = "https://www.googleapis.com/books/v1/volumes?q={$query}&maxResults=40";

    $json = file_get_contents($url);
    if (!$json) continue;

    $data = json_decode($json, true);

    if (!isset($data["items"])) continue;

    foreach ($data["items"] as $item) {

        if (count($collected) >= $booksNeeded) break;

        $info = $item["volumeInfo"];

        // Extract fields with fallback values
        $title = $info["title"] ?? null;
        $authors = $info["authors"][0] ?? "Unknown Author";
        $desc = $info["description"] ?? "No description available.";
        $isbn = $info["industryIdentifiers"][0]["identifier"] ?? null;

        // Skip if title or ISBN missing
        if (!$title || !$isbn) continue;

        // Cover image
        $imageUrl = $info["imageLinks"]["thumbnail"] ?? null;

        // If already collected ISBN, skip
        if (array_key_exists($isbn, $collected)) continue;

        // Download image
        $filename = "public/images/" . str_replace([" ", ":", "/"], "_", $title) . ".jpg";
        if ($imageUrl) {
            $imageContent = @file_get_contents($imageUrl);
            if ($imageContent) {
                file_put_contents($filename, $imageContent);
            } else {
                $filename = "public/images/default.jpg"; // fallback
            }
        } else {
            $filename = "public/images/default.jpg";
        }

        // Add book entry
        $collected[$isbn] = [
            "nazov" => $title,
            "autor" => $authors,
            "popis" => substr($desc, 0, 500), // safety limit
            "cena" => rand(10, 25) + 0.99,   // random realistic price
            "obrazok" => $filename,
            "vazba" => "pevná",
            "pocet" => rand(3, 10),
            "isbn" => $isbn
        ];
    }
}

// ---------------------------
// INSERT INTO DATABASE
// ---------------------------
$stmt = $pdo->prepare("
    INSERT INTO kniha (nazov, autor, popis, cena, obrazok, vazba, pocetNaSklade, isbn)
    VALUES (:nazov, :autor, :popis, :cena, :obrazok, :vazba, :pocet, :isbn)
");

$count = 0;

foreach ($collected as $b) {
    try {
        $stmt->execute([
            ":nazov" => $b["nazov"],
            ":autor" => $b["autor"],
            ":popis" => $b["popis"],
            ":cena" => $b["cena"],
            ":obrazok" => $b["obrazok"],
            ":vazba" => $b["vazba"],
            ":pocet" => $b["pocet"],
            ":isbn" => $b["isbn"]
        ]);
        $count++;
    } catch (Exception $e) {
        // probably duplicate ISBN → skip silently
        continue;
    }
}

echo "<h2>Imported {$count} books successfully.</h2>";
