INSERT INTO serie (name) VALUES
('Mistborn Era 2'),
('The Wheel of Time');

INSERT INTO kniha (nazov, autor, popis, cena, obrazok, vazba, pocetNaSklade, isbn, series_id)
VALUES
-- Mistborn Era 2
(
    'The Alloy of Law',
    'Brandon Sanderson',
    'A new era in the Mistborn world, blending magic, mystery, and a touch of the Wild West.',
    17.99,
    'images/the_alloy_of_law.jpg',
    'pevná',
    8,
    '9780765330423',
    (SELECT id FROM serie WHERE name = 'Mistborn Era 2')
),
(
    'Shadows of Self',
    'Brandon Sanderson',
    'Wax and Wayne return to face political turmoil and a dangerous shape-shifting enemy.',
    18.99,
    'images/shadows_of_self.jpg',
    'pevná',
    7,
    '9780765378555',
    (SELECT id FROM serie WHERE name = 'Mistborn Era 2')
),
(
    'The Bands of Mourning',
    'Brandon Sanderson',
    'A hunt for a mythical artifact leads to shocking discoveries about the world of Scadrial.',
    19.99,
    'images/the_bands_of_mourning.jpg',
    'pevná',
    6,
    '9780765378579',
    (SELECT id FROM serie WHERE name = 'Mistborn Era 2')
),
(
    'The Lost Metal',
    'Brandon Sanderson',
    'The explosive finale of Mistborn Era 2, tying together secrets and new threats to Scadrial.',
    21.99,
    'images/the_lost_metal.jpg',
    'pevná',
    5,
    '9781250318006',
    (SELECT id FROM serie WHERE name = 'Mistborn Era 2')
),

-- Wheel of Time
(
    'The Great Hunt',
    'Robert Jordan',
    'The second book of The Wheel of Time, following the chase for the Horn of Valere.',
    16.99,
    'images/the_great_hunt.jpg',
    'pevná',
    9,
    '9780765305100',
    (SELECT id FROM serie WHERE name = 'The Wheel of Time')
),
(
    'The Dragon Reborn',
    'Robert Jordan',
    'The third book in the series, chronicling Rand al’Thor’s journey toward accepting his destiny.',
    17.99,
    'images/the_dragon_reborn.jpg',
    'pevná',
    8,
    '9780812513714',
    (SELECT id FROM serie WHERE name = 'The Wheel of Time')
),
(
    'The Shadow Rising',
    'Robert Jordan',
    'The fourth installment, expanding the world as Rand and his companions face new challenges.',
    18.99,
    'images/the_shadow_rising.jpg',
    'pevná',
    7,
    '9780812513738',
    (SELECT id FROM serie WHERE name = 'The Wheel of Time')
);


# INSERT INTO serie (name)
# VALUES ('Malazan Book of the Fallen');

INSERT INTO kniha
(nazov, autor, popis, cena, obrazok, vazba, pocetNaSklade, isbn, series_id)
VALUES

-- Malazan Book of the Fallen 1
(
    'Gardens of the Moon',
    'Steven Erikson',
    'The opening novel of the Malazan Book of the Fallen, introducing a vast world of gods, empires, and epic warfare.',
    18.99,
    'gardens_of_the_moon.jpg',
    'pevná',
    6,
    '9780765310012',
    (SELECT id FROM serie WHERE name = 'Malazan Book of the Fallen')
),

-- Malazan Book of the Fallen 2
(
    'Deadhouse Gates',
    'Steven Erikson',
    'The second Malazan novel, following the brutal Chain of Dogs and the cost of empire and survival.',
    19.99,
    'deadhouse_gates.jpg',
    'pevná',
    5,
    '9780765314294',
    (SELECT id FROM serie WHERE name = 'Malazan Book of the Fallen')
),

-- Malazan Book of the Fallen 3
(
    'Memories of Ice',
    'Steven Erikson',
    'A massive convergence of armies, gods, and ancient forces as old conflicts reach their climax.',
    21.99,
    'memories_of_ice.jpg',
    'pevná',
    4,
    '9780765310036',
    (SELECT id FROM serie WHERE name = 'Malazan Book of the Fallen')
),

-- Malazan Book of the Fallen 4
(
    'House of Chains',
    'Steven Erikson',
    'The fourth installment shifts perspective, revealing the origins of a feared adversary and deepening the Malazan mythos.',
    20.99,
    'house_of_chains.jpg',
    'pevná',
    4,
    '9780765315741',
    (SELECT id FROM serie WHERE name = 'Malazan Book of the Fallen')
),

-- Malazan Book of the Fallen 5
(
    'Midnight Tides',
    'Steven Erikson',
    'A new continent, new cultures, and the rise of empires as the Malazan saga expands its scope dramatically.',
    22.99,
    'midnight_tides.jpg',
    'pevná',
    3,
    '9780765316519',
    (SELECT id FROM serie WHERE name = 'Malazan Book of the Fallen')
);


INSERT INTO kniha
(nazov, autor, popis, cena, obrazok, vazba, pocetNaSklade, isbn, series_id)
VALUES ('The Lord of the Rings (All-in-One)',
        'J. R. R. Tolkien',
        'An epic high-fantasy novel that follows the quest to destroy the One Ring and defeat the Dark Lord Sauron.',
        49.99,
        'images/lotr.jpg',
        'pevná',
        6,
        '9780261103252',
        (SELECT id FROM serie WHERE name = 'The Lord of the Rings')),
       ('The Hobbit',
        'J. R. R. Tolkien',
        'A fantasy adventure following Bilbo Baggins on a journey that leads him into the wider world of Middle-earth.',
        29.99,
        'images/hobbit.jpg',
        'pevná',
        10,
        '9780261102217',
        NULL);

UPDATE kniha
SET obrazok = REPLACE(obrazok, 'images/', '');
