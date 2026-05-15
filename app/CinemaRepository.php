<?php

declare(strict_types=1);

final class CinemaRepository
{
    public function __construct(private Database $db, private array $config, private Mailer $mailer)
    {
    }

    public function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    public function appName(): string
    {
        return $this->config['app_name'];
    }

    public function currentDate(): string
    {
        return date('Y-m-d');
    }

    public function heroMovie(): ?array
    {
        $row = $this->db->fetch(
            'SELECT m.*, MIN(s.screening_date || " " || s.screening_time) AS next_screening
             FROM movies m
             JOIN screenings s ON s.movie_id = m.id
             WHERE m.status = "now_showing" AND s.status = "active" AND (s.screening_date || " " || s.screening_time) >= :now
             GROUP BY m.id
             ORDER BY next_screening ASC
             LIMIT 1',
            ['now' => $this->now()]
        );

        return $row ? $this->decorateMovie($row) : null;
    }

    public function nowShowingMovies(int $limit = 8): array
    {
        $rows = $this->db->fetchAll(
            'SELECT DISTINCT m.*
             FROM movies m
             JOIN screenings s ON s.movie_id = m.id
             WHERE m.status = "now_showing" AND s.status = "active" AND s.screening_date >= :today
             ORDER BY s.screening_date ASC, s.screening_time ASC
             LIMIT ' . (int) $limit,
            ['today' => $this->currentDate()]
        );

        return array_map(fn(array $movie): array => $this->decorateMovie($movie), $rows);
    }

    public function comingSoonMovies(int $limit = 6): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM movies WHERE status = "coming_soon" ORDER BY release_date ASC LIMIT ' . (int) $limit
        );

        return array_map(fn(array $movie): array => $this->decorateMovie($movie), $rows);
    }

    public function archivedMovies(int $limit = 8): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM movies WHERE status = "archived" ORDER BY created_at DESC LIMIT ' . (int) $limit
        );

        return array_map(fn(array $movie): array => $this->decorateMovie($movie), $rows);
    }

    public function topReservedMovies(int $limit = 3): array
    {
        $rows = $this->db->fetchAll(
            'SELECT m.*, COUNT(r.id) AS reservations_count
             FROM movies m
             JOIN screenings s ON s.movie_id = m.id
             JOIN reservations r ON r.screening_id = s.id
             WHERE r.status IN ("pending", "paid")
             GROUP BY m.id
             ORDER BY reservations_count DESC, m.title ASC
             LIMIT ' . (int) $limit
        );

        return array_map(fn(array $movie): array => $this->decorateMovie($movie), $rows);
    }

    public function featuredStats(): array
    {
        $reservations = $this->db->fetchAll('SELECT created_at FROM reservations ORDER BY created_at ASC');
        $grouped = [];

        foreach ($reservations as $reservation) {
            $day = date('d.m', strtotime($reservation['created_at']));
            $grouped[$day] = ($grouped[$day] ?? 0) + 1;
        }

        $labels = array_keys($grouped);
        $values = array_values($grouped);

        $topMovie = $this->db->fetch(
            'SELECT m.*, COUNT(r.id) AS reservations_count
             FROM movies m
             JOIN screenings s ON s.movie_id = m.id
             JOIN reservations r ON r.screening_id = s.id
             GROUP BY m.id
             ORDER BY reservations_count DESC
             LIMIT 1'
        );

        return [
            'tickets' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM reservations WHERE status = "paid"'),
            'reservations' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM reservations'),
            'screenings' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM screenings'),
            'movies' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM movies'),
            'top_movie' => $topMovie ? $this->decorateMovie($topMovie) : null,
            'chart' => ['labels' => $labels, 'values' => $values],
        ];
    }

    public function movieById(int $movieId): ?array
    {
        $movie = $this->db->fetch('SELECT * FROM movies WHERE id = :id LIMIT 1', ['id' => $movieId]);

        return $movie ? $this->decorateMovie($movie) : null;
    }

    public function genres(): array
    {
        return $this->db->fetchAll('SELECT id, name, slug FROM genres ORDER BY name ASC');
    }

    public function halls(): array
    {
        return $this->db->fetchAll('SELECT id, name FROM halls ORDER BY sort_order ASC');
    }

    public function filterMovies(array $filters): array
    {
        $movies = array_map(fn(array $movie): array => $this->decorateMovie($movie), $this->db->fetchAll('SELECT * FROM movies ORDER BY title ASC'));

        $filtered = array_filter($movies, function (array $movie) use ($filters): bool {
            if (!empty($filters['search']) && stripos($movie['title'], (string) $filters['search']) === false) {
                return false;
            }

            if (!empty($filters['duration'])) {
                $duration = (int) $movie['duration_minutes'];
                if ($filters['duration'] === 'short' && $duration >= 90) {
                    return false;
                }
                if ($filters['duration'] === 'medium' && ($duration < 90 || $duration > 120)) {
                    return false;
                }
                if ($filters['duration'] === 'long' && $duration <= 120) {
                    return false;
                }
            }

            if (!empty($filters['genre'])) {
                $genreId = (int) $filters['genre'];
                $genreIds = array_map(fn(array $genre): int => (int) $genre['id'], $movie['genres']);
                if (!in_array($genreId, $genreIds, true)) {
                    return false;
                }
            }

            if (!empty($filters['hall']) || !empty($filters['date'])) {
                $screenings = $this->db->fetchAll(
                    'SELECT hall_id, screening_date
                     FROM screenings
                     WHERE movie_id = :movie_id AND status = "active"',
                    ['movie_id' => $movie['id']]
                );

                $matches = false;
                foreach ($screenings as $screening) {
                    if (!empty($filters['hall']) && (int) $screening['hall_id'] !== (int) $filters['hall']) {
                        continue;
                    }
                    if (!empty($filters['date']) && $screening['screening_date'] !== $filters['date']) {
                        continue;
                    }
                    $matches = true;
                    break;
                }

                if (!$matches) {
                    return false;
                }
            }

            return true;
        });

        return array_values($filtered);
    }

    public function movieHasAvailableScreenings(int $movieId): bool
    {
        $count = $this->db->fetchColumn(
            'SELECT COUNT(*)
             FROM screenings s
             JOIN seats seat ON seat.screening_id = s.id
             WHERE s.movie_id = :movie_id AND s.status = "active" AND (s.screening_date || " " || s.screening_time) >= :now AND seat.status = "available"',
            ['movie_id' => $movieId, 'now' => $this->now()]
        );

        return (int) $count > 0;
    }

    public function availableDatesForMovie(int $movieId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT DISTINCT screening_date
             FROM screenings
             WHERE movie_id = :movie_id AND status = "active" AND (screening_date || " " || screening_time) >= :now
             ORDER BY screening_date ASC',
            ['movie_id' => $movieId, 'now' => $this->now()]
        );

        return array_map(fn(array $row): string => $row['screening_date'], $rows);
    }

    public function screeningsForMovieDate(int $movieId, string $date): array
    {
        $buffer = $this->settings()['buffer_minutes'];
        $rows = $this->db->fetchAll(
            'SELECT s.id, s.screening_time, s.hall_id, h.name AS hall_name, m.duration_minutes
             FROM screenings s
             JOIN halls h ON h.id = s.hall_id
             JOIN movies m ON m.id = s.movie_id
             WHERE s.movie_id = :movie_id AND s.screening_date = :screening_date AND s.status = "active"
             ORDER BY s.screening_time ASC, h.sort_order ASC',
            ['movie_id' => $movieId, 'screening_date' => $date]
        );

        foreach ($rows as &$row) {
            $start = strtotime($date . ' ' . $row['screening_time']);
            $totalSeconds = (((int) $row['duration_minutes']) + $buffer) * 60;
            $step = 15 * 60;
            $totalSeconds = (int) ceil($totalSeconds / $step) * $step;
            $row['start'] = date('H:i', $start);
            $row['end'] = date('H:i', $start + $totalSeconds);
            $row['end_time'] = $row['end'];
        }

        return $rows;
    }

    public function seatsForScreening(int $screeningId): array
    {
        $this->expirePendingReservations();

        return $this->db->fetchAll(
            'SELECT id, row_label, seat_number, seat_type, price, status
             FROM seats
             WHERE screening_id = :screening_id
             ORDER BY row_label ASC, seat_number ASC',
            ['screening_id' => $screeningId]
        );
    }

    public function createReservation(int $screeningId, string $seatKeys, string $paymentType, ?int $userId, ?string $guestEmail, ?array $proofFile): array
    {
        $this->expirePendingReservations();

        if ($screeningId <= 0 || trim($seatKeys) === '') {
            throw new RuntimeException('Neispravni podaci.');
        }

        $seatKeysList = array_values(array_filter(array_map('trim', explode(',', $seatKeys))));

        if ($seatKeysList === []) {
            throw new RuntimeException('Nema sjedišta.');
        }

        if (!$userId && (!$guestEmail || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL))) {
            throw new RuntimeException('Email je obavezan za goste.');
        }

        $this->db->begin();

        try {
            $seatRows = [];
            foreach ($seatKeysList as $seatKey) {
                [$rowLabel, $seatNumber] = explode('-', $seatKey);
                $seat = $this->db->fetch(
                    'SELECT * FROM seats
                     WHERE screening_id = :screening_id
                     AND row_label = :row_label
                     AND seat_number = :seat_number
                     AND status = "available"
                     LIMIT 1',
                    [
                        'screening_id' => $screeningId,
                        'row_label' => $rowLabel,
                        'seat_number' => (int) $seatNumber,
                    ]
                );

                if (!$seat) {
                    throw new RuntimeException("Sjedište {$seatKey} nije dostupno.");
                }

                $seatRows[] = $seat;
            }

            $hasWheelchair = false;
            $total = 0.0;
            foreach ($seatRows as $seat) {
                $total += (float) $seat['price'];
                if ($seat['seat_type'] === 'wheelchair') {
                    $hasWheelchair = true;
                }
            }

            if ($hasWheelchair && $paymentType === 'card' && !$proofFile) {
                throw new RuntimeException('Potrebno je uploadovati dokaz o invaliditetu.');
            }

            $proofPath = $proofFile ? $this->storeProofFile($proofFile) : null;
            $status = $paymentType === 'card' ? 'paid' : 'pending';
            $seatStatus = $paymentType === 'card' ? 'occupied' : 'reserved';
            $guestToken = $userId ? null : sha1(($guestEmail ?? '') . microtime(true));
            $paidAt = $status === 'paid' ? $this->now() : null;

            $this->db->execute(
                'INSERT INTO reservations (screening_id, user_id, guest_email, guest_token, total_price, status, disability_proof, created_at, paid_at)
                 VALUES (:screening_id, :user_id, :guest_email, :guest_token, :total_price, :status, :disability_proof, :created_at, :paid_at)',
                [
                    'screening_id' => $screeningId,
                    'user_id' => $userId,
                    'guest_email' => $guestEmail ?: null,
                    'guest_token' => $guestToken,
                    'total_price' => $total,
                    'status' => $status,
                    'disability_proof' => $proofPath,
                    'created_at' => $this->now(),
                    'paid_at' => $paidAt,
                ]
            );

            $reservationId = $this->db->lastInsertId();

            foreach ($seatRows as $seat) {
                $this->db->execute(
                    'INSERT INTO reservation_seats (reservation_id, seat_id) VALUES (:reservation_id, :seat_id)',
                    ['reservation_id' => $reservationId, 'seat_id' => (int) $seat['id']]
                );
                $this->db->execute(
                    'UPDATE seats SET status = :status WHERE id = :id',
                    ['status' => $seatStatus, 'id' => (int) $seat['id']]
                );
            }

            $this->db->commit();

            $mailResult = null;
            if (!$userId && $guestEmail) {
                $mailResult = $this->logTicketMail($reservationId);
            }

            return [
                'reservation_id' => $reservationId,
                'guest' => !$userId,
                'mail' => $mailResult,
            ];
        } catch (Throwable $throwable) {
            $this->db->rollBack();
            throw $throwable;
        }
    }

    public function payPendingReservation(int $reservationId, int $userId, ?array $proofFile): array
    {
        $reservation = $this->db->fetch(
            'SELECT * FROM reservations WHERE id = :id AND user_id = :user_id AND status = "pending" LIMIT 1',
            ['id' => $reservationId, 'user_id' => $userId]
        );

        if (!$reservation) {
            throw new RuntimeException('Rezervacija ne postoji, nije vaša ili je već plaćena.');
        }

        return $this->markReservationAsPaid($reservationId, $proofFile, false);
    }

    public function payGuestPendingReservation(int $reservationId, string $token, ?array $proofFile): array
    {
        $reservation = $this->db->fetch(
            'SELECT * FROM reservations WHERE id = :id AND guest_token = :token AND status = "pending" LIMIT 1',
            ['id' => $reservationId, 'token' => $token]
        );

        if (!$reservation) {
            throw new RuntimeException('Rezervacija ne postoji, nije validna ili je već plaćena.');
        }

        return $this->markReservationAsPaid($reservationId, $proofFile, true);
    }

    private function markReservationAsPaid(int $reservationId, ?array $proofFile, bool $guest): array
    {
        $hasWheelchair = (int) $this->db->fetchColumn(
            'SELECT COUNT(*)
             FROM reservation_seats rs
             JOIN seats s ON s.id = rs.seat_id
             WHERE rs.reservation_id = :reservation_id AND s.seat_type = "wheelchair"',
            ['reservation_id' => $reservationId]
        ) > 0;

        if ($hasWheelchair && !$proofFile) {
            throw new RuntimeException('Morate uploadovati dokaz o invaliditetu.');
        }

        $proofPath = $proofFile ? $this->storeProofFile($proofFile) : null;

        $this->db->begin();

        try {
            $params = [
                'id' => $reservationId,
                'paid_at' => $this->now(),
            ];

            $sql = 'UPDATE reservations SET status = "paid", paid_at = :paid_at';
            if ($proofPath) {
                $sql .= ', disability_proof = :proof';
                $params['proof'] = $proofPath;
            }
            $sql .= ' WHERE id = :id';

            $this->db->execute($sql, $params);
            $this->db->execute(
                'UPDATE seats
                 SET status = "occupied"
                 WHERE id IN (
                     SELECT seat_id FROM reservation_seats WHERE reservation_id = :reservation_id
                 )',
                ['reservation_id' => $reservationId]
            );

            $this->db->commit();

            return ['guest' => $guest];
        } catch (Throwable $throwable) {
            $this->db->rollBack();
            throw $throwable;
        }
    }

    public function userReservations(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT r.id AS reservation_id, r.status, r.total_price, r.guest_token, r.created_at, r.paid_at,
                    m.id AS movie_id, m.title, m.poster_path,
                    s.screening_date, s.screening_time,
                    h.name AS hall_name
             FROM reservations r
             JOIN screenings s ON s.id = r.screening_id
             JOIN movies m ON m.id = s.movie_id
             JOIN halls h ON h.id = s.hall_id
             WHERE r.user_id = :user_id AND r.status IN ("pending", "paid")
             ORDER BY s.screening_date DESC, s.screening_time DESC',
            ['user_id' => $userId]
        );

        foreach ($rows as &$row) {
            $row['poster_url'] = media_url($row['poster_path']);
            $row['seats'] = $this->reservationSeatLabels((int) $row['reservation_id']);
            $row['seat_type'] = $this->reservationSeatType((int) $row['reservation_id']);
            $row['ticket_code'] = 'MC-' . str_pad((string) $row['reservation_id'], 6, '0', STR_PAD_LEFT);
            $row['download_url'] = url_for('karta-preuzimanje?id=' . $row['reservation_id']);
            $row['cancel_url'] = url_for('otkazi-rezervaciju?rid=' . $row['reservation_id']);
        }

        return $rows;
    }

    public function cancelReservationForUser(int $reservationId, int $userId): void
    {
        $reservation = $this->db->fetch(
            'SELECT id FROM reservations WHERE id = :id AND user_id = :user_id LIMIT 1',
            ['id' => $reservationId, 'user_id' => $userId]
        );

        if (!$reservation) {
            throw new RuntimeException('Rezervacija nije pronađena.');
        }

        $this->cancelReservation($reservationId);
    }

    public function reservationForCancelPage(int $reservationId, string $token): ?array
    {
        $reservation = $this->db->fetch(
            'SELECT r.*, m.title, h.name AS hall_name, s.screening_date, s.screening_time
             FROM reservations r
             JOIN screenings s ON s.id = r.screening_id
             JOIN movies m ON m.id = s.movie_id
             JOIN halls h ON h.id = s.hall_id
             WHERE r.id = :id AND r.guest_token = :token LIMIT 1',
            ['id' => $reservationId, 'token' => $token]
        );

        if (!$reservation) {
            return null;
        }

        $reservation['seat_type'] = $this->reservationSeatType($reservationId);
        $reservation['needs_proof'] = $reservation['seat_type'] === 'wheelchair';

        return $reservation;
    }

    public function cancelReservationForGuest(int $reservationId, string $token): void
    {
        $reservation = $this->reservationForCancelPage($reservationId, $token);

        if (!$reservation) {
            throw new RuntimeException('Rezervacija nije pronađena.');
        }

        $this->cancelReservation($reservationId);
    }

    public function reservationTicketForUser(int $reservationId, int $userId): ?array
    {
        $reservation = $this->db->fetch(
            'SELECT r.id, r.total_price, m.title, s.screening_date, s.screening_time, h.name AS hall_name
             FROM reservations r
             JOIN screenings s ON s.id = r.screening_id
             JOIN movies m ON m.id = s.movie_id
             JOIN halls h ON h.id = s.hall_id
             WHERE r.id = :id AND r.user_id = :user_id AND r.status = "paid"
             LIMIT 1',
            ['id' => $reservationId, 'user_id' => $userId]
        );

        if (!$reservation) {
            return null;
        }

        $reservation['seat_labels'] = $this->reservationSeatLabels($reservationId);
        $reservation['ticket_code'] = 'MC-' . str_pad((string) $reservationId, 6, '0', STR_PAD_LEFT);

        return $reservation;
    }

    public function reviewStats(int $movieId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT r.*, COALESCE(r.author_name, u.full_name, SUBSTR(r.guest_email, 1, INSTR(r.guest_email, "@") - 1), "Gost") AS display_name
             FROM reviews r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.movie_id = :movie_id
             ORDER BY r.created_at DESC',
            ['movie_id' => $movieId]
        );

        $total = count($rows);
        $sum = array_sum(array_map(fn(array $row): int => (int) $row['rating'], $rows));
        $avg = $total > 0 ? round($sum / $total, 1) : 0;

        return [
            'reviews' => array_map(function (array $row): array {
                return [
                    'display_name' => $row['display_name'],
                    'rating' => (int) $row['rating'],
                    'comment' => $row['comment'],
                    'verified' => !empty($row['user_id']) ? 1 : 0,
                    'date' => date('d.m.Y', strtotime($row['created_at'])),
                ];
            }, $rows),
            'stats' => [
                'total' => $total,
                'avg_rating' => $avg,
            ],
        ];
    }

    public function submitReview(int $movieId, int $rating, string $comment, ?int $userId, ?string $guestEmail): void
    {
        if ($movieId <= 0 || $rating < 1 || $rating > 5) {
            throw new RuntimeException('Neispravni podaci.');
        }

        if (!$userId && (!$guestEmail || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL))) {
            throw new RuntimeException('Niste prepoznati kao korisnik.');
        }

        if (!$this->userCanReviewMovie($movieId, $userId, $guestEmail)) {
            throw new RuntimeException('Možete ostaviti recenziju samo ako ste rezervisali film.');
        }

        $params = ['movie_id' => $movieId];
        $where = 'movie_id = :movie_id AND ';

        if ($userId) {
            $where .= 'user_id = :user_id';
            $params['user_id'] = $userId;
        } else {
            $where .= 'guest_email = :guest_email';
            $params['guest_email'] = $guestEmail;
        }

        $exists = (int) $this->db->fetchColumn('SELECT COUNT(*) FROM reviews WHERE ' . $where, $params);
        if ($exists > 0) {
            throw new RuntimeException('Već ste ostavili recenziju za ovaj film.');
        }

        $authorName = null;
        if ($userId) {
            $user = $this->db->fetch('SELECT full_name FROM users WHERE id = :id', ['id' => $userId]);
            $authorName = $user['full_name'] ?? null;
        }

        $this->db->execute(
            'INSERT INTO reviews (movie_id, user_id, guest_email, author_name, rating, comment, created_at)
             VALUES (:movie_id, :user_id, :guest_email, :author_name, :rating, :comment, :created_at)',
            [
                'movie_id' => $movieId,
                'user_id' => $userId,
                'guest_email' => $guestEmail ?: null,
                'author_name' => $authorName,
                'rating' => $rating,
                'comment' => trim($comment),
                'created_at' => $this->now(),
            ]
        );
    }

    public function movieOptionsForAdmin(): array
    {
        $movies = $this->db->fetchAll('SELECT * FROM movies WHERE status != "archived" ORDER BY title ASC');

        return array_map(fn(array $movie): array => $this->decorateMovie($movie), $movies);
    }

    public function screeningsTable(): array
    {
        return $this->db->fetchAll(
            'SELECT s.id, s.screening_date, s.screening_time, s.price, s.status, m.title AS movie_title, h.name AS hall_name
             FROM screenings s
             JOIN movies m ON m.id = s.movie_id
             JOIN halls h ON h.id = s.hall_id
             ORDER BY s.screening_date ASC, s.screening_time ASC, h.sort_order ASC'
        );
    }

    public function screeningFilters(array $filters): array
    {
        $rows = $this->screeningsTable();

        return array_values(array_filter($rows, function (array $row) use ($filters): bool {
            if (!empty($filters['date_from']) && $row['screening_date'] < $filters['date_from']) {
                return false;
            }
            if (!empty($filters['date_to']) && $row['screening_date'] > $filters['date_to']) {
                return false;
            }
            if (!empty($filters['hall_id']) && (int) $this->hallIdByName($row['hall_name']) !== (int) $filters['hall_id']) {
                return false;
            }
            if (!empty($filters['movie_id']) && (int) $this->movieIdByTitle($row['movie_title']) !== (int) $filters['movie_id']) {
                return false;
            }
            if (!empty($filters['status']) && $row['status'] !== $filters['status']) {
                return false;
            }
            if (!empty($filters['search']) && stripos($row['movie_title'], $filters['search']) === false) {
                return false;
            }

            return true;
        }));
    }

    public function availableTimes(int $movieId, int $hallId, string $date): array
    {
        if ($movieId <= 0 || $hallId <= 0 || $date === '') {
            return [];
        }

        $settings = $this->settings();
        $duration = (int) $this->db->fetchColumn('SELECT duration_minutes FROM movies WHERE id = :id', ['id' => $movieId]);
        if ($duration <= 0) {
            return [];
        }

        $step = 15 * 60;
        $open = strtotime($date . ' ' . $settings['open_time']);
        $close = strtotime($date . ' ' . $settings['close_time']);
        $open = (int) ceil($open / $step) * $step;

        $totalSeconds = ($duration + (int) $settings['buffer_minutes']) * 60;
        $totalSeconds = (int) ceil($totalSeconds / $step) * $step;

        $existing = $this->db->fetchAll(
            'SELECT s.screening_time, m.duration_minutes
             FROM screenings s
             JOIN movies m ON m.id = s.movie_id
             WHERE s.hall_id = :hall_id AND s.screening_date = :screening_date AND s.status = "active"',
            ['hall_id' => $hallId, 'screening_date' => $date]
        );

        $blocked = [];
        foreach ($existing as $row) {
            $start = strtotime($date . ' ' . $row['screening_time']);
            $seconds = (((int) $row['duration_minutes']) + (int) $settings['buffer_minutes']) * 60;
            $seconds = (int) ceil($seconds / $step) * $step;
            $blocked[] = [$start, $start + $seconds];
        }

        $available = [];
        for ($time = $open; $time + $totalSeconds <= $close; $time += $step) {
            $conflict = false;
            foreach ($blocked as [$blockedStart, $blockedEnd]) {
                if ($time < $blockedEnd && ($time + $totalSeconds) > $blockedStart) {
                    $conflict = true;
                    break;
                }
            }
            if (!$conflict) {
                $available[] = date('H:i', $time);
            }
        }

        return $available;
    }

    public function addScreening(int $movieId, int $hallId, string $date, string $time, float $price): int
    {
        if ($movieId <= 0 || $hallId <= 0 || !$date || !$time || $price <= 0) {
            throw new RuntimeException('Morate popuniti sva obavezna polja.');
        }

        $available = $this->availableTimes($movieId, $hallId, $date);
        if (!in_array($time, $available, true)) {
            throw new RuntimeException('Odabrani termin nije dostupan.');
        }

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
                'created_at' => $this->now(),
            ]
        );

        $screeningId = $this->db->lastInsertId();
        $this->generateSeatsForNewScreening($screeningId, $hallId, $price);

        return $screeningId;
    }

    public function autoGenerateSchedule(array $movieIds, string $dateFrom, string $dateTo): int
    {
        $movieIds = array_values(array_filter(array_map('intval', $movieIds)));
        if (count($movieIds) < 2) {
            throw new RuntimeException('Odaberite barem 2 filma.');
        }
        if (!$dateFrom || !$dateTo) {
            throw new RuntimeException('Nisu poslani datumi.');
        }

        $rules = [
            'blockbuster' => ['max_per_day' => 6, 'window' => ['16:00', '22:30']],
            'standard' => ['max_per_day' => 3, 'window' => ['12:00', '20:00']],
            'arthouse' => ['max_per_day' => 2, 'window' => ['10:00', '18:00']],
            'kids' => ['max_per_day' => 3, 'window' => ['10:00', '16:00']],
            'horror' => ['max_per_day' => 2, 'window' => ['20:00', '23:30']],
        ];

        $movies = [];
        foreach ($movieIds as $movieId) {
            $movie = $this->movieById($movieId);
            if ($movie && $movie['status'] === 'now_showing') {
                $movies[] = $movie;
            }
        }

        $halls = $this->halls();
        if ($movies === [] || $halls === []) {
            throw new RuntimeException('Nema filmova ili sala.');
        }

        $created = 0;
        $movieIndex = 0;
        $start = new DateTimeImmutable($dateFrom);
        $end = new DateTimeImmutable($dateTo);

        for ($day = $start; $day <= $end; $day = $day->modify('+1 day')) {
            $dailyMovieCount = [];
            foreach ($halls as $hall) {
                $perHallCount = 0;
                $attempts = 0;

                while ($perHallCount < 4 && $attempts < 12) {
                    $attempts++;
                    $candidate = $movies[$movieIndex % count($movies)];
                    $movieIndex++;

                    $type = $this->movieType($candidate);
                    $limit = $rules[$type]['max_per_day'] ?? 2;
                    $currentCount = $dailyMovieCount[$candidate['id']] ?? 0;

                    if ($currentCount >= $limit) {
                        continue;
                    }

                    $times = $this->availableTimes((int) $candidate['id'], (int) $hall['id'], $day->format('Y-m-d'));
                    [$from, $to] = $rules[$type]['window'];
                    $times = array_values(array_filter($times, fn(string $time): bool => $time >= $from && $time <= $to));

                    if ($times === []) {
                        continue;
                    }

                    $price = $hall['name'] === 'VIP Sala' ? 8 : ($hall['name'] === 'Sala 3 IMAX' ? 7 : 6);
                    $this->addScreening((int) $candidate['id'], (int) $hall['id'], $day->format('Y-m-d'), $times[0], (float) $price);

                    $dailyMovieCount[$candidate['id']] = $currentCount + 1;
                    $created++;
                    $perHallCount++;
                }
            }
        }

        return $created;
    }

    public function settings(): array
    {
        return $this->db->fetch('SELECT open_time, close_time, buffer_minutes FROM settings WHERE id = 1') ?: [
            'open_time' => '10:00',
            'close_time' => '23:45',
            'buffer_minutes' => 15,
        ];
    }

    public function renderMovieCard(array $movie): string
    {
        $genres = $movie['genres'] ?? $this->movieGenres((int) $movie['id']);
        $canReserve = $movie['status'] === 'now_showing' && $this->movieHasAvailableScreenings((int) $movie['id']);

        ob_start();
        ?>
        <div class="mc-movie-card state-<?= e($movie['status']) ?>">
          <?php if ($movie['status'] === 'coming_soon'): ?>
            <div class="mc-movie-badge soon">Uskoro</div>
          <?php elseif ($movie['status'] === 'archived'): ?>
            <div class="mc-movie-badge archived">Završeno prikazivanje</div>
          <?php endif; ?>
          <img src="<?= e($movie['poster_url']) ?>" alt="<?= e($movie['title']) ?>" class="mc-movie-poster">
          <?php if ($movie['status'] === 'coming_soon' && !empty($movie['release_date'])): ?>
            <div style="text-align:center;margin-top:10px;font-size:14px;font-weight:600;color:#ffffffcc">
              Početak prikazivanja: <?= e(format_date_local($movie['release_date'])) ?>
            </div>
          <?php endif; ?>
          <div class="mc-movie-body">
            <h4><?= e($movie['title']) ?></h4>
            <div class="mc-movie-meta">
              <img src="<?= e(media_url('2026/02/clock-4.png')) ?>" alt="">
              <?= (int) $movie['duration_minutes'] ?> min
            </div>
            <?php if ($genres): ?>
              <div class="mc-movie-genres">
                <?php foreach ($genres as $genre): ?>
                  <span class="tag tag-<?= e($genre['slug']) ?>"><?= e($genre['name']) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <div class="mc-card-actions">
              <a href="<?= e(movie_link((int) $movie['id'])) ?>" class="btn-primary-card">
                <span>Saznaj više</span>
              </a>
              <?php if ($canReserve): ?>
                <a href="<?= e(reservation_link((int) $movie['id'])) ?>" class="btn-primary-reservation">
                  <span>Rezerviši</span>
                  <img src="<?= e(media_url('2026/02/ticket.png')) ?>" alt="">
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php

        return trim((string) ob_get_clean());
    }

    private function decorateMovie(array $movie): array
    {
        $movie['poster_url'] = media_url($movie['poster_path']);
        $movie['hero_url'] = media_url($movie['hero_path']);
        $movie['genres'] = $this->movieGenres((int) $movie['id']);

        return $movie;
    }

    private function movieGenres(int $movieId): array
    {
        return $this->db->fetchAll(
            'SELECT g.id, g.name, g.slug
             FROM genres g
             JOIN movie_genres mg ON mg.genre_id = g.id
             WHERE mg.movie_id = :movie_id
             ORDER BY g.name ASC',
            ['movie_id' => $movieId]
        );
    }

    private function reservationSeatLabels(int $reservationId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT s.row_label, s.seat_number
             FROM reservation_seats rs
             JOIN seats s ON s.id = rs.seat_id
             WHERE rs.reservation_id = :reservation_id
             ORDER BY s.row_label ASC, s.seat_number ASC',
            ['reservation_id' => $reservationId]
        );

        return array_map(fn(array $row): string => $row['row_label'] . '-' . $row['seat_number'], $rows);
    }

    private function reservationSeatType(int $reservationId): string
    {
        $types = $this->db->fetchAll(
            'SELECT s.seat_type
             FROM reservation_seats rs
             JOIN seats s ON s.id = rs.seat_id
             WHERE rs.reservation_id = :reservation_id',
            ['reservation_id' => $reservationId]
        );

        $values = array_map(fn(array $row): string => $row['seat_type'], $types);
        if (in_array('wheelchair', $values, true)) {
            return 'wheelchair';
        }
        if (in_array('love', $values, true)) {
            return 'love';
        }
        if (in_array('vip', $values, true)) {
            return 'vip';
        }

        return 'standard';
    }

    private function storeProofFile(array $proofFile): string
    {
        if (($proofFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Greška pri uploadu fajla.');
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        $mime = mime_content_type($proofFile['tmp_name']) ?: ($proofFile['type'] ?? '');
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Dozvoljeni su samo JPG, PNG, WEBP i PDF fajlovi.');
        }

        $name = 'proof-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
        $dir = __DIR__ . '/../storage/uploads/proofs';

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $target = $dir . '/' . $name;

        if (!move_uploaded_file($proofFile['tmp_name'], $target)) {
            throw new RuntimeException('Greška pri spremanju fajla.');
        }

        return 'storage/uploads/proofs/' . $name;
    }

    private function logTicketMail(int $reservationId): ?array
    {
        $reservation = $this->db->fetch(
            'SELECT r.*, m.title, s.screening_date, s.screening_time, h.name AS hall_name
             FROM reservations r
             JOIN screenings s ON s.id = r.screening_id
             JOIN movies m ON m.id = s.movie_id
             JOIN halls h ON h.id = s.hall_id
             WHERE r.id = :id',
            ['id' => $reservationId]
        );

        if (!$reservation || empty($reservation['guest_email'])) {
            return null;
        }

        return $this->mailer->sendReservationTicket([
            'to' => $reservation['guest_email'],
            'movie' => $reservation['title'],
            'date' => format_date_local($reservation['screening_date']),
            'time' => format_time_local($reservation['screening_time']),
            'hall' => $reservation['hall_name'],
            'seats' => $this->reservationSeatLabels((int) $reservation['id']),
            'payment_status' => $reservation['status'] === 'paid' ? 'Plaćena' : 'Nije plaćena',
            'starts_at' => trim((string) $reservation['screening_date'] . ' ' . (string) $reservation['screening_time']),
            'qr_code_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . rawurlencode('MC-' . str_pad((string) $reservation['id'], 6, '0', STR_PAD_LEFT)),
            'pay_url' => current_full_url('otkazi-rezervaciju', [
                'rid' => $reservation['id'],
                'token' => $reservation['guest_token'],
                'mode' => 'pay',
            ]),
            'cancel_url' => current_full_url('otkazi-rezervaciju', [
                'rid' => $reservation['id'],
                'token' => $reservation['guest_token'],
                'mode' => 'cancel',
            ]),
        ]);
    }

    private function expirePendingReservations(): void
    {
        $threshold = date('Y-m-d H:i:s', time() - (15 * 60));
        $expired = $this->db->fetchAll(
            'SELECT id FROM reservations WHERE status = "pending" AND created_at < :threshold',
            ['threshold' => $threshold]
        );

        foreach ($expired as $reservation) {
            $this->cancelReservation((int) $reservation['id']);
        }
    }

    private function cancelReservation(int $reservationId): void
    {
        $this->db->begin();

        try {
            $this->db->execute(
                'UPDATE seats
                 SET status = "available"
                 WHERE id IN (SELECT seat_id FROM reservation_seats WHERE reservation_id = :reservation_id)',
                ['reservation_id' => $reservationId]
            );

            $this->db->execute(
                'UPDATE reservations SET status = "cancelled" WHERE id = :id',
                ['id' => $reservationId]
            );

            $this->db->commit();
        } catch (Throwable $throwable) {
            $this->db->rollBack();
            throw $throwable;
        }
    }

    private function userCanReviewMovie(int $movieId, ?int $userId, ?string $guestEmail): bool
    {
        if ($userId) {
            $count = $this->db->fetchColumn(
                'SELECT COUNT(*)
                 FROM reservations r
                 JOIN screenings s ON s.id = r.screening_id
                 WHERE s.movie_id = :movie_id AND r.user_id = :user_id',
                ['movie_id' => $movieId, 'user_id' => $userId]
            );

            return (int) $count > 0;
        }

        $count = $this->db->fetchColumn(
            'SELECT COUNT(*)
             FROM reservations r
             JOIN screenings s ON s.id = r.screening_id
             WHERE s.movie_id = :movie_id AND r.guest_email = :guest_email',
            ['movie_id' => $movieId, 'guest_email' => $guestEmail]
        );

        return (int) $count > 0;
    }

    private function movieType(array $movie): string
    {
        $slugs = array_map(fn(array $genre): string => $genre['slug'], $movie['genres']);

        if (in_array('akcija', $slugs, true)) {
            return 'blockbuster';
        }
        if (in_array('horor', $slugs, true)) {
            return 'horror';
        }
        if (in_array('komedija', $slugs, true)) {
            return 'kids';
        }
        if (in_array('drama', $slugs, true) || in_array('dokumentarni', $slugs, true)) {
            return 'arthouse';
        }

        return 'standard';
    }

    private function generateSeatsForNewScreening(int $screeningId, int $hallId, float $price): void
    {
        $layout = $this->db->fetchAll(
            'SELECT row_label, seat_number, seat_type FROM hall_seat_layout WHERE hall_id = :hall_id ORDER BY row_label, seat_number',
            ['hall_id' => $hallId]
        );

        foreach ($layout as $seat) {
            $seatPrice = match ($seat['seat_type']) {
                'vip' => $price + 2,
                'love' => $price + 3,
                'wheelchair' => max(4, $price - 1),
                default => $price,
            };

            $this->db->execute(
                'INSERT INTO seats (screening_id, row_label, seat_number, seat_type, price, status)
                 VALUES (:screening_id, :row_label, :seat_number, :seat_type, :price, :status)',
                [
                    'screening_id' => $screeningId,
                    'row_label' => $seat['row_label'],
                    'seat_number' => $seat['seat_number'],
                    'seat_type' => $seat['seat_type'],
                    'price' => $seatPrice,
                    'status' => 'available',
                ]
            );
        }
    }

    private function hallIdByName(string $name): int
    {
        return (int) $this->db->fetchColumn('SELECT id FROM halls WHERE name = :name', ['name' => $name]);
    }

    private function movieIdByTitle(string $title): int
    {
        return (int) $this->db->fetchColumn('SELECT id FROM movies WHERE title = :title', ['title' => $title]);
    }
}
