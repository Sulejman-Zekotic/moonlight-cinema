<?php

declare(strict_types=1);

final class Installer
{
    public function __construct(private Database $db, private array $config)
    {
    }

    public function ensureReady(): void
    {
        if (!$this->schemaExists()) {
            $this->createSchema();
            $this->seedData();
        }

        $this->ensureSchemaUpgrades();
    }

    private function schemaExists(): bool
    {
        return $this->tableExists('movies');
    }

    private function ensureSchemaUpgrades(): void
    {
        $this->ensurePasswordResetTable();
    }

    private function ensurePasswordResetTable(): void
    {
        if ($this->tableExists('password_resets')) {
            return;
        }

        if ($this->db->driver() === 'mysql') {
            $this->db->execute(
                'CREATE TABLE password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token_hash VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    used_at DATETIME NULL,
                    created_at DATETIME NOT NULL,
                    UNIQUE KEY uniq_password_resets_token_hash (token_hash),
                    INDEX idx_password_resets_user_id (user_id),
                    INDEX idx_password_resets_expires_at (expires_at),
                    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );

            return;
        }

        $this->db->execute(
            'CREATE TABLE password_resets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token_hash TEXT NOT NULL UNIQUE,
                expires_at TEXT NOT NULL,
                used_at TEXT,
                created_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )'
        );
    }

    private function tableExists(string $tableName): bool
    {
        if ($this->db->driver() === 'sqlite') {
            $row = $this->db->fetch(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = :table_name",
                ['table_name' => $tableName]
            );

            return $row !== null;
        }

        $row = $this->db->fetch('SHOW TABLES LIKE :table_name', ['table_name' => $tableName]);

        return $row !== null;
    }

    private function createSchema(): void
    {
        if ($this->db->driver() === 'mysql') {
            $queries = [
                'CREATE TABLE users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    full_name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    role VARCHAR(50) NOT NULL DEFAULT "user",
                    created_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                'CREATE TABLE movies (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL UNIQUE,
                    duration_minutes INT NOT NULL,
                    director VARCHAR(255) NULL,
                    description TEXT NOT NULL,
                    hero_excerpt TEXT NULL,
                    trailer_url TEXT NULL,
                    poster_path TEXT NOT NULL,
                    hero_path TEXT NOT NULL,
                    status VARCHAR(50) NOT NULL,
                    release_date DATE NULL,
                    created_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                'CREATE TABLE genres (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(120) NOT NULL,
                    slug VARCHAR(120) NOT NULL UNIQUE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                'CREATE TABLE movie_genres (
                    movie_id INT NOT NULL,
                    genre_id INT NOT NULL,
                    PRIMARY KEY (movie_id, genre_id),
                    CONSTRAINT fk_movie_genres_movie FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
                    CONSTRAINT fk_movie_genres_genre FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                'CREATE TABLE halls (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(120) NOT NULL,
                    sort_order INT NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                'CREATE TABLE hall_seat_layout (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    hall_id INT NOT NULL,
                    row_label VARCHAR(10) NOT NULL,
                    seat_number INT NOT NULL,
                    seat_type VARCHAR(50) NOT NULL DEFAULT "standard",
                    CONSTRAINT fk_hall_seat_layout_hall FOREIGN KEY (hall_id) REFERENCES halls(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                'CREATE TABLE screenings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    movie_id INT NOT NULL,
                    hall_id INT NOT NULL,
                    screening_date DATE NOT NULL,
                    screening_time TIME NOT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    status VARCHAR(50) NOT NULL DEFAULT "active",
                    created_at DATETIME NOT NULL,
                    INDEX idx_screenings_date_time (screening_date, screening_time),
                    CONSTRAINT fk_screenings_movie FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
                    CONSTRAINT fk_screenings_hall FOREIGN KEY (hall_id) REFERENCES halls(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                'CREATE TABLE seats (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    screening_id INT NOT NULL,
                    row_label VARCHAR(10) NOT NULL,
                    seat_number INT NOT NULL,
                    seat_type VARCHAR(50) NOT NULL DEFAULT "standard",
                    price DECIMAL(10,2) NOT NULL,
                    status VARCHAR(50) NOT NULL DEFAULT "available",
                    INDEX idx_seats_screening (screening_id),
                    CONSTRAINT fk_seats_screening FOREIGN KEY (screening_id) REFERENCES screenings(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                'CREATE TABLE reservations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    screening_id INT NOT NULL,
                    user_id INT NULL,
                    guest_email VARCHAR(255) NULL,
                    guest_token VARCHAR(255) NULL,
                    total_price DECIMAL(10,2) NOT NULL,
                    status VARCHAR(50) NOT NULL,
                    disability_proof TEXT NULL,
                    created_at DATETIME NOT NULL,
                    paid_at DATETIME NULL,
                    INDEX idx_reservations_screening (screening_id),
                    INDEX idx_reservations_user (user_id),
                    CONSTRAINT fk_reservations_screening FOREIGN KEY (screening_id) REFERENCES screenings(id) ON DELETE CASCADE,
                    CONSTRAINT fk_reservations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                'CREATE TABLE reservation_seats (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    reservation_id INT NOT NULL,
                    seat_id INT NOT NULL,
                    INDEX idx_reservation_seats_reservation (reservation_id),
                    INDEX idx_reservation_seats_seat (seat_id),
                    CONSTRAINT fk_reservation_seats_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
                    CONSTRAINT fk_reservation_seats_seat FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                'CREATE TABLE reviews (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    movie_id INT NOT NULL,
                    user_id INT NULL,
                    guest_email VARCHAR(255) NULL,
                    author_name VARCHAR(255) NULL,
                    rating INT NOT NULL,
                    comment TEXT NOT NULL,
                    created_at DATETIME NOT NULL,
                    INDEX idx_reviews_movie (movie_id),
                    CONSTRAINT fk_reviews_movie FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
                    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                'CREATE TABLE settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    open_time TIME NOT NULL,
                    close_time TIME NOT NULL,
                    buffer_minutes INT NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            ];
        } else {
            $queries = [
                'CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    full_name TEXT NOT NULL,
                    email TEXT NOT NULL UNIQUE,
                    password_hash TEXT NOT NULL,
                    role TEXT NOT NULL DEFAULT "user",
                    created_at TEXT NOT NULL
                )',
                'CREATE TABLE movies (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT NOT NULL,
                    slug TEXT NOT NULL UNIQUE,
                    duration_minutes INTEGER NOT NULL,
                    director TEXT,
                    description TEXT NOT NULL,
                    hero_excerpt TEXT,
                    trailer_url TEXT,
                    poster_path TEXT NOT NULL,
                    hero_path TEXT NOT NULL,
                    status TEXT NOT NULL,
                    release_date TEXT,
                    created_at TEXT NOT NULL
                )',
                'CREATE TABLE genres (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    slug TEXT NOT NULL UNIQUE
                )',
                'CREATE TABLE movie_genres (
                    movie_id INTEGER NOT NULL,
                    genre_id INTEGER NOT NULL,
                    PRIMARY KEY (movie_id, genre_id),
                    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
                    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
                )',
                'CREATE TABLE halls (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    sort_order INTEGER NOT NULL
                )',
                'CREATE TABLE hall_seat_layout (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    hall_id INTEGER NOT NULL,
                    row_label TEXT NOT NULL,
                    seat_number INTEGER NOT NULL,
                    seat_type TEXT NOT NULL DEFAULT "standard",
                    FOREIGN KEY (hall_id) REFERENCES halls(id) ON DELETE CASCADE
                )',
                'CREATE TABLE screenings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    movie_id INTEGER NOT NULL,
                    hall_id INTEGER NOT NULL,
                    screening_date TEXT NOT NULL,
                    screening_time TEXT NOT NULL,
                    price REAL NOT NULL,
                    status TEXT NOT NULL DEFAULT "active",
                    created_at TEXT NOT NULL,
                    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
                    FOREIGN KEY (hall_id) REFERENCES halls(id) ON DELETE CASCADE
                )',
                'CREATE TABLE seats (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    screening_id INTEGER NOT NULL,
                    row_label TEXT NOT NULL,
                    seat_number INTEGER NOT NULL,
                    seat_type TEXT NOT NULL DEFAULT "standard",
                    price REAL NOT NULL,
                    status TEXT NOT NULL DEFAULT "available",
                    FOREIGN KEY (screening_id) REFERENCES screenings(id) ON DELETE CASCADE
                )',
                'CREATE TABLE reservations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    screening_id INTEGER NOT NULL,
                    user_id INTEGER,
                    guest_email TEXT,
                    guest_token TEXT,
                    total_price REAL NOT NULL,
                    status TEXT NOT NULL,
                    disability_proof TEXT,
                    created_at TEXT NOT NULL,
                    paid_at TEXT,
                    FOREIGN KEY (screening_id) REFERENCES screenings(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                )',
                'CREATE TABLE reservation_seats (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    reservation_id INTEGER NOT NULL,
                    seat_id INTEGER NOT NULL,
                    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
                    FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE CASCADE
                )',
                'CREATE TABLE reviews (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    movie_id INTEGER NOT NULL,
                    user_id INTEGER,
                    guest_email TEXT,
                    author_name TEXT,
                    rating INTEGER NOT NULL,
                    comment TEXT NOT NULL,
                    created_at TEXT NOT NULL,
                    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                )',
                'CREATE TABLE settings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    open_time TEXT NOT NULL,
                    close_time TEXT NOT NULL,
                    buffer_minutes INTEGER NOT NULL
                )',
            ];
        }

        foreach ($queries as $query) {
            $this->db->execute($query);
        }
    }

    private function seedData(): void
    {
        $now = date('Y-m-d H:i:s');
        $today = new DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

        $this->db->begin();

        try {
            $this->db->execute(
                'INSERT INTO settings (open_time, close_time, buffer_minutes) VALUES (:open_time, :close_time, :buffer_minutes)',
                ['open_time' => '10:00', 'close_time' => '23:45', 'buffer_minutes' => 15]
            );

            $users = [
                ['full_name' => 'Admin Moonlight', 'email' => 'admin@moonlightcinema.ba', 'role' => 'admin', 'password' => 'Admin123!'],
                ['full_name' => 'Sulejman', 'email' => 'sulejman@moonlightcinema.ba', 'role' => 'user', 'password' => 'Moonlight123!'],
                ['full_name' => 'Amar Brkić', 'email' => 'amar@moonlightcinema.ba', 'role' => 'user', 'password' => 'Moonlight123!'],
                ['full_name' => 'Senad Granulo', 'email' => 'senad@moonlightcinema.ba', 'role' => 'user', 'password' => 'Moonlight123!'],
                ['full_name' => 'Irma Mandžuka', 'email' => 'irma@moonlightcinema.ba', 'role' => 'user', 'password' => 'Moonlight123!'],
            ];

            foreach ($users as $user) {
                $this->db->execute(
                    'INSERT INTO users (full_name, email, password_hash, role, created_at) VALUES (:full_name, :email, :password_hash, :role, :created_at)',
                    [
                        'full_name' => $user['full_name'],
                        'email' => $user['email'],
                        'password_hash' => password_hash($user['password'], PASSWORD_DEFAULT),
                        'role' => $user['role'],
                        'created_at' => $now,
                    ]
                );
            }

            $genres = [
                ['name' => 'Akcija', 'slug' => 'akcija'],
                ['name' => 'Triler', 'slug' => 'triler'],
                ['name' => 'Drama', 'slug' => 'drama'],
                ['name' => 'Komedija', 'slug' => 'komedija'],
                ['name' => 'Romantika', 'slug' => 'romantika'],
                ['name' => 'Krimi', 'slug' => 'krimi'],
                ['name' => 'Horor', 'slug' => 'horor'],
                ['name' => 'Dokumentarni', 'slug' => 'dokumentarni'],
            ];

            foreach ($genres as $genre) {
                $this->db->execute(
                    'INSERT INTO genres (name, slug) VALUES (:name, :slug)',
                    $genre
                );
            }

            $genreIds = [];
            foreach ($this->db->fetchAll('SELECT id, slug FROM genres') as $genre) {
                $genreIds[$genre['slug']] = (int) $genre['id'];
            }

            $movies = [
                ['title' => 'The Beekeeper', 'slug' => 'the-beekeeper', 'duration' => 105, 'director' => 'David Ayer', 'description' => 'Napeti akcioni triler koji prati povučenog, naizgled običnog čovjeka čiji miran život biva brutalno prekinut. Iza njegove tišine krije se prošlost opasnijeg karaktera nego što iko može zamisliti.', 'hero_excerpt' => 'Akcioni triler koji prati bivšeg agenta u nemilosrdnoj borbi protiv kriminalne mreže.', 'trailer' => 'https://www.youtube.com/watch?v=SzINZZ6iqxY', 'poster' => '2026/02/cuvarpcelamala.jpg', 'hero' => '2026/01/beekeer-hero.png', 'status' => 'now_showing'],
                ['title' => 'Extraction', 'slug' => 'extraction', 'duration' => 116, 'director' => 'Sam Hargrave', 'description' => 'Bivši specijalac preuzima nemoguću misiju spašavanja otetog dječaka u srcu opasnog podzemlja. Film donosi intenzivnu akciju, napete potjere i sirovu energiju iz minute u minutu.', 'hero_excerpt' => 'Eksplozivna spasilačka misija u kojoj svaka odluka košta.', 'trailer' => 'https://www.youtube.com/watch?v=L6P3nI6VnlY', 'poster' => '2026/02/extractionMala.jpg', 'hero' => '2026/02/extractionVelika.jpg', 'status' => 'now_showing'],
                ['title' => 'Free Solo', 'slug' => 'free-solo', 'duration' => 100, 'director' => 'Jimmy Chin', 'description' => 'Dokumentarni film o usponu bez osiguranja na El Capitan, jednom od najtežih penjanja na svijetu. Istovremeno je lična priča o disciplini, fokusu i granicama ljudske izdržljivosti.', 'hero_excerpt' => 'Spektakularan dokumentarac o podvigu koji pomjera granice mogućeg.', 'trailer' => 'https://www.youtube.com/watch?v=urRVZ4SW7WU', 'poster' => '2026/02/freesolomala.jpg', 'hero' => '2026/02/freeSoloVelika.jpg', 'status' => 'now_showing'],
                ['title' => 'Sicario', 'slug' => 'sicario', 'duration' => 121, 'director' => 'Denis Villeneuve', 'description' => 'Mračna priča o ratu protiv kartela, moralnim kompromisima i opasnim granicama između pravde i osvete. Zategnuta atmosfera i vrhunska gluma nose film od početka do kraja.', 'hero_excerpt' => 'Napeta priča o ratu protiv kartela i cijeni pravde.', 'trailer' => 'https://www.youtube.com/watch?v=YxH4TPQAbMg', 'poster' => '2026/02/sicario.jpg', 'hero' => '2026/02/sicarioVelika.jpeg', 'status' => 'now_showing'],
                ['title' => 'Anyone But You', 'slug' => 'anyone-but-you', 'duration' => 103, 'director' => 'Will Gluck', 'description' => 'Romantična komedija o dvoje ljudi koji se ne podnose, ali splet okolnosti ih primorava da glume idealan par. Lagan ton i duhoviti dijalozi čine je savršenim izborom za večernji izlazak.', 'hero_excerpt' => 'Lagana i zabavna romantična komedija sa puno hemije i humora.', 'trailer' => 'https://www.youtube.com/watch?v=UtjH6Sk7Gxs', 'poster' => '2026/02/anyone-but-you.jpg', 'hero' => '2026/01/any-hero.png', 'status' => 'now_showing'],
                ['title' => 'The Bourne Ultimatum', 'slug' => 'the-bourne-ultimatum', 'duration' => 115, 'director' => 'Paul Greengrass', 'description' => 'Jason Bourne nastavlja lov na vlastitu prošlost dok ga tajne službe pokušavaju ukloniti. Brza montaža, međunarodne lokacije i neprestana napetost film čine klasikom žanra.', 'hero_excerpt' => 'Potjera bez predaha dok Bourne pokušava otkriti ko je zaista.', 'trailer' => 'https://www.youtube.com/watch?v=ZT2ZxjUjSo0', 'poster' => '2025/12/bourne.jpg', 'hero' => '2026/01/ultimatum-hero.png', 'status' => 'now_showing'],
                ['title' => 'Longlegs', 'slug' => 'longlegs', 'duration' => 101, 'director' => 'Oz Perkins', 'description' => 'Neobičan psihološki horor sa uznemirujućom atmosferom i misterioznim tragovima koji se polako sklapaju u jezivu cjelinu. Film je idealan za kasne večernje termine.', 'hero_excerpt' => 'Psihološki horor sa tihom, ali upornom nelagodom.', 'trailer' => 'https://www.youtube.com/watch?v=48wg-_VzD04', 'poster' => '2025/12/longlegs.jpeg', 'hero' => '2026/01/longlegs-hero.png', 'status' => 'now_showing'],
                ['title' => 'The Big Sick', 'slug' => 'the-big-sick', 'duration' => 120, 'director' => 'Michael Showalter', 'description' => 'Topla drama i romantična priča inspirisana stvarnim događajima. Film balansira humor, porodične razlike i emotivne trenutke bez patetike.', 'hero_excerpt' => 'Topla i duhovita priča o ljubavi, porodici i neočekivanim izazovima.', 'trailer' => 'https://www.youtube.com/watch?v=jcD0Daqc3Yw', 'poster' => '2026/02/thebigsick.jpg', 'hero' => '2026/02/thbigsickvelika.jpg', 'status' => 'now_showing'],
                ['title' => 'The Mitchells vs. the Machines', 'slug' => 'the-mitchells-vs-the-machines', 'duration' => 114, 'director' => 'Michael Rianda', 'description' => 'Animirana avantura o neobičnoj porodici koja se neočekivano nađe u središtu tehnološke apokalipse. Film kombinuje humor, akciju i toplu porodičnu priču.', 'hero_excerpt' => 'Brza, šarena i zabavna animirana avantura za cijelu porodicu.', 'trailer' => 'https://www.youtube.com/watch?v=_ak5dFt8Ar0', 'poster' => '2026/02/masinemala.png', 'hero' => '2026/02/masineVelika.jpg', 'status' => 'now_showing'],
                ['title' => 'The Strangers', 'slug' => 'the-strangers', 'duration' => 86, 'director' => 'Renny Harlin', 'description' => 'Intiman horor o paru koji se suočava s prijetnjom iz tame, bez jasnog motiva i bez sigurnog izlaza. Gradacija napetosti je stalna i nepopustljiva.', 'hero_excerpt' => 'Horor sa pritiskom tišine i nepredvidivom prijetnjom iz mraka.', 'trailer' => 'https://www.youtube.com/watch?v=fwWBmK1uDgM', 'poster' => '2026/02/stranciMala.jpg', 'hero' => '2026/02/stranciVelika.jpg', 'status' => 'now_showing'],
                ['title' => '13 Hours: The Secret Soldiers of Benghazi', 'slug' => '13-hours', 'duration' => 144, 'director' => 'Michael Bay', 'description' => 'Ratna akcijska drama o grupi operativaca koji pokušavaju zadržati položaj u nemogućim uslovima. Film je intenzivan, glasan i ritmički neumoljiv.', 'hero_excerpt' => 'Ratna akcija visokog intenziteta sa snažnim pritiskom vremena.', 'trailer' => 'https://www.youtube.com/watch?v=kuhoMZmSEHw', 'poster' => '2026/02/13.jpg', 'hero' => '2026/02/13satijfiVelika.jpg', 'status' => 'coming_soon', 'release_date' => $tomorrow->modify('+6 day')->format('Y-m-d')],
            ];

            foreach ($movies as $movie) {
                $this->db->execute(
                    'INSERT INTO movies (title, slug, duration_minutes, director, description, hero_excerpt, trailer_url, poster_path, hero_path, status, release_date, created_at)
                     VALUES (:title, :slug, :duration_minutes, :director, :description, :hero_excerpt, :trailer_url, :poster_path, :hero_path, :status, :release_date, :created_at)',
                    [
                        'title' => $movie['title'],
                        'slug' => $movie['slug'],
                        'duration_minutes' => $movie['duration'],
                        'director' => $movie['director'],
                        'description' => $movie['description'],
                        'hero_excerpt' => $movie['hero_excerpt'],
                        'trailer_url' => $movie['trailer'],
                        'poster_path' => $movie['poster'],
                        'hero_path' => $movie['hero'],
                        'status' => $movie['status'],
                        'release_date' => $movie['release_date'] ?? null,
                        'created_at' => $now,
                    ]
                );
            }

            $movieIds = [];
            foreach ($this->db->fetchAll('SELECT id, slug FROM movies') as $movie) {
                $movieIds[$movie['slug']] = (int) $movie['id'];
            }

            $movieGenres = [
                'the-beekeeper' => ['akcija', 'triler'],
                'extraction' => ['akcija', 'drama'],
                'free-solo' => ['drama', 'dokumentarni'],
                'sicario' => ['akcija', 'triler', 'krimi'],
                'anyone-but-you' => ['drama', 'romantika', 'komedija'],
                'the-bourne-ultimatum' => ['akcija', 'triler'],
                'longlegs' => ['horor'],
                'the-big-sick' => ['drama', 'romantika'],
                'the-mitchells-vs-the-machines' => ['drama', 'komedija'],
                'the-strangers' => ['horor', 'krimi'],
                '13-hours' => ['akcija', 'drama'],
            ];

            foreach ($movieGenres as $slug => $slugs) {
                foreach ($slugs as $genreSlug) {
                    $this->db->execute(
                        'INSERT INTO movie_genres (movie_id, genre_id) VALUES (:movie_id, :genre_id)',
                        ['movie_id' => $movieIds[$slug], 'genre_id' => $genreIds[$genreSlug]]
                    );
                }
            }

            $halls = [
                ['name' => 'Sala 1', 'sort_order' => 1],
                ['name' => 'Sala 2', 'sort_order' => 2],
                ['name' => 'VIP Sala', 'sort_order' => 3],
                ['name' => 'Sala 3 IMAX', 'sort_order' => 4],
            ];

            foreach ($halls as $hall) {
                $this->db->execute(
                    'INSERT INTO halls (name, sort_order) VALUES (:name, :sort_order)',
                    $hall
                );
            }

            $hallIds = [];
            foreach ($this->db->fetchAll('SELECT id, name FROM halls') as $hall) {
                $hallIds[$hall['name']] = (int) $hall['id'];
            }

            $this->seedHallLayouts($hallIds);
            $this->seedScreenings($movieIds, $hallIds, $tomorrow, $now);
            $this->seedReservationsAndReviews($movieIds, $now, $today);

            $this->db->commit();
        } catch (Throwable $throwable) {
            $this->db->rollBack();
            throw $throwable;
        }
    }

    private function seedHallLayouts(array $hallIds): void
    {
        $layouts = [
            'Sala 1' => $this->buildLayout(range('A', 'G'), 12, [
                'G-1' => 'wheelchair',
                'G-12' => 'wheelchair',
                'D-5' => 'love',
                'D-6' => 'love',
            ]),
            'Sala 2' => $this->buildLayout(range('A', 'G'), 14, [
                'D-1' => 'standard',
                'E-1' => 'wheelchair',
                'E-14' => 'wheelchair',
                'C-8' => 'love',
                'C-9' => 'love',
                'D-9' => 'love',
                'D-10' => 'love',
                'D-12' => 'love',
                'D-13' => 'love',
            ]),
            'VIP Sala' => $this->buildLayout(range('A', 'G'), 14, [
                'G-1' => 'wheelchair',
                'G-14' => 'wheelchair',
                'C-8' => 'love',
                'C-9' => 'love',
                'D-7' => 'love',
                'D-8' => 'love',
                'D-9' => 'love',
                'D-10' => 'love',
                'E-8' => 'love',
                'E-9' => 'love',
                'F-8' => 'vip',
                'F-9' => 'vip',
            ]),
            'Sala 3 IMAX' => $this->buildLayout(range('A', 'H'), 16, [
                'H-1' => 'wheelchair',
                'H-16' => 'wheelchair',
                'D-7' => 'love',
                'D-8' => 'love',
                'D-9' => 'love',
                'D-10' => 'love',
                'E-7' => 'vip',
                'E-8' => 'vip',
                'E-9' => 'vip',
                'E-10' => 'vip',
            ]),
        ];

        foreach ($layouts as $hallName => $layout) {
            $hallId = $hallIds[$hallName];

            foreach ($layout as $seat) {
                $this->db->execute(
                    'INSERT INTO hall_seat_layout (hall_id, row_label, seat_number, seat_type) VALUES (:hall_id, :row_label, :seat_number, :seat_type)',
                    [
                        'hall_id' => $hallId,
                        'row_label' => $seat['row_label'],
                        'seat_number' => $seat['seat_number'],
                        'seat_type' => $seat['seat_type'],
                    ]
                );
            }
        }
    }

    private function buildLayout(array $rows, int $seatsPerRow, array $specialSeats): array
    {
        $layout = [];

        foreach ($rows as $row) {
            for ($seat = 1; $seat <= $seatsPerRow; $seat++) {
                $key = $row . '-' . $seat;
                $layout[] = [
                    'row_label' => $row,
                    'seat_number' => $seat,
                    'seat_type' => $specialSeats[$key] ?? 'standard',
                ];
            }
        }

        return $layout;
    }

    private function seedScreenings(array $movieIds, array $hallIds, DateTimeImmutable $tomorrow, string $createdAt): void
    {
        $patterns = [
            [$movieIds['the-beekeeper'], $hallIds['Sala 1'], '14:00', 8],
            [$movieIds['the-beekeeper'], $hallIds['VIP Sala'], '16:00', 8],
            [$movieIds['extraction'], $hallIds['Sala 2'], '18:30', 8],
            [$movieIds['sicario'], $hallIds['Sala 3 IMAX'], '20:00', 8],
            [$movieIds['the-mitchells-vs-the-machines'], $hallIds['Sala 1'], '10:00', 5],
            [$movieIds['anyone-but-you'], $hallIds['Sala 2'], '12:00', 5],
            [$movieIds['the-big-sick'], $hallIds['Sala 2'], '16:00', 5],
            [$movieIds['the-strangers'], $hallIds['Sala 2'], '21:30', 5],
            [$movieIds['free-solo'], $hallIds['VIP Sala'], '12:30', 5],
            [$movieIds['the-bourne-ultimatum'], $hallIds['Sala 3 IMAX'], '17:00', 5],
            [$movieIds['longlegs'], $hallIds['Sala 1'], '22:00', 4],
            [$movieIds['13-hours'], $hallIds['Sala 3 IMAX'], '13:00', 2],
        ];

        foreach ($patterns as [$movieId, $hallId, $time, $days]) {
            for ($offset = 0; $offset < $days; $offset++) {
                $date = $tomorrow->modify('+' . $offset . ' day')->format('Y-m-d');
                $price = $hallId === $hallIds['VIP Sala'] ? 8.0 : ($hallId === $hallIds['Sala 3 IMAX'] ? 7.0 : 6.0);

                $this->db->execute(
                    'INSERT INTO screenings (movie_id, hall_id, screening_date, screening_time, price, status, created_at)
                     VALUES (:movie_id, :hall_id, :screening_date, :screening_time, :price, :status, :created_at)',
                    [
                        'movie_id' => $movieId,
                        'hall_id' => $hallId,
                        'screening_date' => $date,
                        'screening_time' => $time,
                        'price' => $price,
                        'status' => 'active',
                        'created_at' => $createdAt,
                    ]
                );

                $screeningId = $this->db->lastInsertId();
                $this->generateSeatsForScreening($screeningId, $hallId, $price);
            }
        }
    }

    private function generateSeatsForScreening(int $screeningId, int $hallId, float $basePrice): void
    {
        $layout = $this->db->fetchAll(
            'SELECT row_label, seat_number, seat_type FROM hall_seat_layout WHERE hall_id = :hall_id ORDER BY row_label, seat_number',
            ['hall_id' => $hallId]
        );

        foreach ($layout as $seat) {
            $price = match ($seat['seat_type']) {
                'vip' => $basePrice + 2,
                'love' => $basePrice + 3,
                'wheelchair' => max(4, $basePrice - 1),
                default => $basePrice,
            };

            $this->db->execute(
                'INSERT INTO seats (screening_id, row_label, seat_number, seat_type, price, status)
                 VALUES (:screening_id, :row_label, :seat_number, :seat_type, :price, :status)',
                [
                    'screening_id' => $screeningId,
                    'row_label' => $seat['row_label'],
                    'seat_number' => $seat['seat_number'],
                    'seat_type' => $seat['seat_type'],
                    'price' => $price,
                    'status' => 'available',
                ]
            );
        }
    }

    private function seedReservationsAndReviews(array $movieIds, string $now, DateTimeImmutable $today): void
    {
        $users = [];
        foreach ($this->db->fetchAll('SELECT id, email FROM users') as $user) {
            $users[$user['email']] = (int) $user['id'];
        }

        $screenings = $this->db->fetchAll('SELECT id, movie_id, screening_date FROM screenings ORDER BY screening_date, screening_time');
        $screeningByMovie = [];
        foreach ($screenings as $screening) {
            $screeningByMovie[(int) $screening['movie_id']][] = $screening;
        }

        $reservationPlan = [
            ['movie' => 'the-mitchells-vs-the-machines', 'user' => 'sulejman@moonlightcinema.ba', 'status' => 'paid', 'count' => 2, 'created_at' => $today->modify('-1 day')->format('Y-m-d 10:15:00')],
            ['movie' => 'the-mitchells-vs-the-machines', 'user' => 'amar@moonlightcinema.ba', 'status' => 'paid', 'count' => 2, 'created_at' => $today->modify('-2 day')->format('Y-m-d 12:40:00')],
            ['movie' => 'the-mitchells-vs-the-machines', 'user' => 'senad@moonlightcinema.ba', 'status' => 'paid', 'count' => 2, 'created_at' => $today->modify('-3 day')->format('Y-m-d 11:20:00')],
            ['movie' => 'the-mitchells-vs-the-machines', 'user' => 'irma@moonlightcinema.ba', 'status' => 'paid', 'count' => 2, 'created_at' => $today->modify('-4 day')->format('Y-m-d 09:50:00')],
            ['movie' => 'the-mitchells-vs-the-machines', 'guest_email' => 'porodica@example.com', 'status' => 'pending', 'count' => 2, 'created_at' => $today->modify('-1 day')->format('Y-m-d 14:05:00')],
            ['movie' => 'the-beekeeper', 'user' => 'sulejman@moonlightcinema.ba', 'status' => 'paid', 'count' => 2, 'created_at' => $today->modify('-1 day')->format('Y-m-d 18:30:00')],
            ['movie' => 'the-beekeeper', 'guest_email' => 'rezervacija@example.com', 'status' => 'pending', 'count' => 1, 'created_at' => $today->format('Y-m-d 08:45:00')],
            ['movie' => 'sicario', 'user' => 'amar@moonlightcinema.ba', 'status' => 'paid', 'count' => 2, 'created_at' => $today->modify('-2 day')->format('Y-m-d 21:00:00')],
            ['movie' => 'the-big-sick', 'user' => 'senad@moonlightcinema.ba', 'status' => 'paid', 'count' => 1, 'created_at' => $today->modify('-5 day')->format('Y-m-d 13:05:00')],
            ['movie' => 'anyone-but-you', 'user' => 'irma@moonlightcinema.ba', 'status' => 'paid', 'count' => 2, 'created_at' => $today->modify('-6 day')->format('Y-m-d 16:15:00')],
            ['movie' => 'the-strangers', 'guest_email' => 'horrorfan@example.com', 'status' => 'pending', 'count' => 1, 'created_at' => $now],
            ['movie' => 'free-solo', 'guest_email' => 'alex@example.com', 'status' => 'paid', 'count' => 1, 'created_at' => $today->modify('-2 day')->format('Y-m-d 10:10:00')],
            ['movie' => 'extraction', 'user' => 'sulejman@moonlightcinema.ba', 'status' => 'paid', 'count' => 1, 'created_at' => $today->modify('-3 day')->format('Y-m-d 17:25:00')],
        ];

        foreach ($reservationPlan as $plan) {
            $movieId = $movieIds[$plan['movie']];
            $screening = $screeningByMovie[$movieId][0] ?? null;

            if (!$screening) {
                continue;
            }

            $availableSeats = $this->db->fetchAll(
                'SELECT id, price FROM seats WHERE screening_id = :screening_id AND status = "available" ORDER BY row_label, seat_number LIMIT ' . (int) $plan['count'],
                ['screening_id' => $screening['id']]
            );

            if (count($availableSeats) < $plan['count']) {
                continue;
            }

            $total = array_sum(array_map(fn(array $seat): float => (float) $seat['price'], $availableSeats));
            $guestToken = !empty($plan['guest_email']) ? sha1(($plan['guest_email'] ?? '') . $plan['created_at']) : null;
            $userId = !empty($plan['user']) ? $users[$plan['user']] : null;

            $this->db->execute(
                'INSERT INTO reservations (screening_id, user_id, guest_email, guest_token, total_price, status, created_at, paid_at)
                 VALUES (:screening_id, :user_id, :guest_email, :guest_token, :total_price, :status, :created_at, :paid_at)',
                [
                    'screening_id' => $screening['id'],
                    'user_id' => $userId,
                    'guest_email' => $plan['guest_email'] ?? null,
                    'guest_token' => $guestToken,
                    'total_price' => $total,
                    'status' => $plan['status'],
                    'created_at' => $plan['created_at'],
                    'paid_at' => $plan['status'] === 'paid' ? $plan['created_at'] : null,
                ]
            );

            $reservationId = $this->db->lastInsertId();
            $seatStatus = $plan['status'] === 'paid' ? 'occupied' : 'reserved';

            foreach ($availableSeats as $seat) {
                $this->db->execute(
                    'INSERT INTO reservation_seats (reservation_id, seat_id) VALUES (:reservation_id, :seat_id)',
                    ['reservation_id' => $reservationId, 'seat_id' => $seat['id']]
                );
                $this->db->execute(
                    'UPDATE seats SET status = :status WHERE id = :id',
                    ['status' => $seatStatus, 'id' => $seat['id']]
                );
            }
        }

        $reviews = [
            ['movie' => 'the-beekeeper', 'user' => 'amar@moonlightcinema.ba', 'rating' => 2, 'comment' => 'Film mi nije ostavio neki poseban utisak. Priča je u redu, ali ništa posebno, i mogao je biti zanimljiviji. Može se pogledati, ali nije film koji bih posebno preporučio.', 'created_at' => $today->modify('-6 day')->format('Y-m-d 19:20:00')],
            ['movie' => 'the-beekeeper', 'user' => 'senad@moonlightcinema.ba', 'rating' => 4, 'comment' => 'Nakon gledanja filma ostao sam s jako dobrim utiskom. Priča je lijepo ispričana, likovi su zanimljivi, a film ima pozitivnu poruku. Preporučujem ga svima koji žele uživati u dobrom filmu.', 'created_at' => $today->modify('-6 day')->format('Y-m-d 19:30:00')],
            ['movie' => 'the-beekeeper', 'user' => 'irma@moonlightcinema.ba', 'rating' => 5, 'comment' => 'Film mi se svidio jer je zanimljiv i lagan za gledanje. Ima lijepu priču i ugodnu atmosferu. Preporučujem ga.', 'created_at' => $today->modify('-6 day')->format('Y-m-d 19:35:00')],
        ];

        foreach ($reviews as $review) {
            $userId = $users[$review['user']] ?? null;
            $author = $this->db->fetch('SELECT full_name FROM users WHERE id = :id', ['id' => $userId]);

            $this->db->execute(
                'INSERT INTO reviews (movie_id, user_id, guest_email, author_name, rating, comment, created_at)
                 VALUES (:movie_id, :user_id, :guest_email, :author_name, :rating, :comment, :created_at)',
                [
                    'movie_id' => $movieIds[$review['movie']],
                    'user_id' => $userId,
                    'guest_email' => null,
                    'author_name' => $author['full_name'] ?? null,
                    'rating' => $review['rating'],
                    'comment' => $review['comment'],
                    'created_at' => $review['created_at'],
                ]
            );
        }
    }
}
