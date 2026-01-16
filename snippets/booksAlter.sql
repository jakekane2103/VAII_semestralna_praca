
# CREATE TABLE serie (
#                         id INT AUTO_INCREMENT PRIMARY KEY,
#                         name VARCHAR(255) NOT NULL UNIQUE
# );

# INSERT INTO serie (name) VALUES
#                               ('The Lord of the Rings'),
#                               ('Stormlight Archive'),
#                               ('The Kingkiller Chronicle'),
#                               ('The Witcher'),
#                               ('Mistborn Era 1'),
#                               ('Mistborn Era 2'),
#                               ('A Song of Ice and Fire'),
#                               ('Harry Potter'),
#                               ('Percy Jackson'),
#                               ('The Wheel of Time');

#ALTER TABLE kniha ADD COLUMN series_id INT NULL;

# UPDATE kniha b
#     JOIN serie s
#     ON b.seria COLLATE utf8mb4_general_ci
#         = s.name COLLATE utf8mb4_general_ci
# SET b.series_id = s.id;

# UPDATE kniha b
# set series_id = 5
# where series_id = 1;

#ALTER TABLE kniha DROP COLUMN seria;

UPDATE kniha
SET obrazok = REPLACE(obrazok, 'images/', '');
