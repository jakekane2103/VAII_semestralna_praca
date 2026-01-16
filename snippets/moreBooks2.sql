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
