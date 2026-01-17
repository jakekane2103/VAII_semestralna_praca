# INSERT INTO kniha
# (nazov, autor, popis, cena, obrazok, vazba, pocetNaSklade, isbn, series_id)
# VALUES
#     (
#         'The Silmarillion',
#         'J. R. R. Tolkien',
#         'A collection of mythological stories that form the foundation of Middle-earth, from the creation of the world to the end of the First Age.',
#         34.99,
#         'the_silmarillion',
#         'pevná',
#         7,
#         '9780261102736',
#         NULL
#     );
# INSERT INTO kniha
# (nazov, autor, popis, cena, obrazok, vazba, pocetNaSklade, isbn, series_id)
# VALUES
#     (
#         'The Fall of Gondolin',
#         'J. R. R. Tolkien',
#         'One of the Great Tales of Middle-earth, telling the story of the hidden city of Gondolin and its tragic fall.',
#         32.99,
#         'the_fall_of_gondolin',
#         'pevná',
#         6,
#         '9780008302757',
#         NULL
#     );
# INSERT INTO kniha
# (nazov, autor, popis, cena, obrazok, vazba, pocetNaSklade, isbn, series_id)
# VALUES
#     (
#         'The Blade Itself',
#         'Joe Abercrombie',
#         'The first book in The First Law trilogy, introducing a grimdark world of brutal politics, flawed heroes, and sharp steel.',
#         27.99,
#         'the_blade_itself',
#         'pevná',
#         9,
#         '9780575079793',
#         (SELECT id FROM serie WHERE name = 'The First Law')
#     );
UPDATE kniha
SET obrazok = CONCAT(obrazok, '.jpg')
WHERE nazov IN (
                'The Silmarillion',
                'The Fall of Gondolin',
                'The Blade Itself'
    );
