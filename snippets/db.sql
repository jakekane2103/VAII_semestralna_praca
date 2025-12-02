CREATE TABLE `kniha` (
                         `id_kniha` INT(11) NOT NULL AUTO_INCREMENT,
                         `nazov` VARCHAR(255) NOT NULL,
                         `autor` VARCHAR(255) NOT NULL,
                         `obrazok` VARCHAR(300) DEFAULT NULL,
                         `popis` TEXT DEFAULT NULL,
                         `vazba` ENUM('pevná','mäkká','ebook') DEFAULT NULL,
                         `cena` DECIMAL(10,2) NOT NULL,
                         `pocetNaSklade` INT(11) NOT NULL DEFAULT 0,
                         `ISBN` VARCHAR(50) DEFAULT NULL,
                         PRIMARY KEY (`id_kniha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `zakaznik` (
                            `id_zakaznik` INT(11) NOT NULL AUTO_INCREMENT,
                            `pouzivatelske_meno` VARCHAR(255) NOT NULL,
                            `meno` VARCHAR(255) NOT NULL,
                            `priezvisko` VARCHAR(255) NOT NULL,
                            `email` VARCHAR(255) NOT NULL,
                            `heslo` VARCHAR(255) NOT NULL, -- hash hesla sa štandardne ukladá ako varchar
                            `krajina` VARCHAR(255) DEFAULT NULL,
                            `mesto` VARCHAR(255) DEFAULT NULL,
                            `psc` VARCHAR(255) DEFAULT NULL,
                            `ulica` VARCHAR(255) DEFAULT NULL,
                            `cislo` VARCHAR(255) DEFAULT NULL,
                            `datum_registracie` DATETIME NOT NULL,
                            PRIMARY KEY (`id_zakaznik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `kosik` (
                         `id_kosik` INT(11) NOT NULL AUTO_INCREMENT,
                         `id_zakaznik` INT(11) NOT NULL,
                         UNIQUE (`id_zakaznik`),
                         PRIMARY KEY (`id_kosik`),
                         FOREIGN KEY (`id_zakaznik`) REFERENCES `zakaznik` (`id_zakaznik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `kosikKniha` (
                              `id_kosik` INT(11) NOT NULL,
                              `id_kniha` INT(11) NOT NULL,
                              `mnozstvo` INT(11) NOT NULL DEFAULT 1,
                              PRIMARY KEY (`id_kosik`, `id_kniha`),
                              FOREIGN KEY (`id_kosik`) REFERENCES `kosik` (`id_kosik`),
                              FOREIGN KEY (`id_kniha`) REFERENCES `kniha` (`id_kniha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `wishlist` (
                            `id_wishlist` INT(11) NOT NULL AUTO_INCREMENT,
                            `id_zakaznik` INT(11) NOT NULL,
                            `title` VARCHAR(255) NOT NULL,
                            `datum_pridania` DATETIME NOT NULL,
                            UNIQUE (`id_zakaznik`),
                            PRIMARY KEY (`id_wishlist`),
                            FOREIGN KEY (`id_zakaznik`) REFERENCES `zakaznik` (`id_zakaznik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `wishlistKniha` (
                                 `id_wishlist` INT(11) NOT NULL,
                                 `id_kniha` INT(11) NOT NULL,
                                 PRIMARY KEY (`id_wishlist`, `id_kniha`),
                                 FOREIGN KEY (`id_wishlist`) REFERENCES `wishlist` (`id_wishlist`),
                                 FOREIGN KEY (`id_kniha`) REFERENCES `kniha` (`id_kniha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `objednavka` (
                              `id_objednavka` INT(11) NOT NULL AUTO_INCREMENT,
                              `id_zakaznik` INT(11) NOT NULL,
                              `datum_vytvorenia` DATETIME NOT NULL,
                              `stav` ENUM('nova','zaplacena','odoslana','dokončena','zrusena') NOT NULL,
                              `mnozstvo` INT NOT NULL,
                              `celkova_cena` DECIMAL(10,2) NOT NULL,
                              PRIMARY KEY (`id_objednavka`),
                              FOREIGN KEY (`id_zakaznik`) REFERENCES `zakaznik` (`id_zakaznik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `polozkaObjednavky` (
                                     `id_polozka` INT(11) NOT NULL AUTO_INCREMENT,
                                     `id_kniha` INT(11) NOT NULL,
                                     `id_objednavka` INT(11) NOT NULL,
                                     `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     PRIMARY KEY (`id_polozka`),
                                     FOREIGN KEY (`id_kniha`) REFERENCES `kniha` (`id_kniha`),
                                     FOREIGN KEY (`id_objednavka`) REFERENCES `objednavka` (`id_objednavka`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
