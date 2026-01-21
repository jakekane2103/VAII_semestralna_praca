<?php

namespace App\Models;

use Framework\DB\Connection;

class Wit
{
    // Return a random line from witWisdom or null when none found
    public static function randomLine(): ?string
    {
        $conn = Connection::getInstance();
        $stmt = $conn->prepare('SELECT line FROM witWisdom ORDER BY RAND() LIMIT 1');
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row && isset($row['line'])) return $row['line'];
        return null;
    }
}

