<?php

declare(strict_types=1);

$repo = $app['repo'];
$auth = $app['auth'];
$mailer = $app['mailer'];
$action = (string) request_value('action', '');

try {
    switch ($action) {
        case 'mc_app_login':
            $result = $auth->login((string) request_value('email', ''), (string) request_value('password', ''));
            if (!$result['success']) {
                json_error($result['message']);
            }
            json_success([
                'message' => $result['message'],
                'role' => $result['role'],
                'name' => $result['name'],
            ]);

        case 'mc_app_register':
            $result = $auth->register(
                (string) request_value('name', ''),
                (string) request_value('email', ''),
                (string) request_value('password', '')
            );
            if (!$result['success']) {
                json_error($result['message']);
            }
            json_success(['message' => $result['message']]);

        case 'mc_request_password_reset':
            $result = $auth->requestPasswordReset((string) request_value('email', ''), $mailer);
            if (!$result['success']) {
                json_error($result['message']);
            }
            json_success(['message' => $result['message']]);

        case 'mc_reset_password':
            $result = $auth->resetPassword((string) request_value('token', ''), (string) request_value('password', ''));
            if (!$result['success']) {
                json_error($result['message']);
            }
            json_success(['message' => $result['message']]);

        case 'mc_app_logout':
            $auth->logout();
            json_success('Odjavljeni ste.');

        case 'mc_get_movies':
            json_success(array_map(
                static fn(array $movie): array => ['id' => $movie['id'], 'title' => $movie['title']],
                $repo->nowShowingMovies(20)
            ));

        case 'mc_get_movie_by_id':
            $movie = $repo->movieById((int) request_value('id', 0));
            if (!$movie) {
                json_error('Film nije pronađen.', 404);
            }
            json_success($movie);

        case 'mc_get_available_dates_for_movie':
            $dates = $repo->availableDatesForMovie((int) request_value('movie_id', 0));
            if ($dates === []) {
                json_error('Nema projekcija za ovaj film.');
            }
            json_success($dates);

        case 'mc_get_screenings':
            $screenings = $repo->screeningsForMovieDate((int) request_value('movie_id', 0), (string) request_value('date', ''));
            if ($screenings === []) {
                json_error('Nema termina za odabrani datum.');
            }
            json_success($screenings);

        case 'mc_get_seats':
            json_success($repo->seatsForScreening((int) request_value('screening_id', 0)));

        case 'mc_create_reservation':
            $result = $repo->createReservation(
                (int) request_value('screening_id', 0),
                (string) request_value('seats', ''),
                (string) request_value('payment_type', 'cash'),
                $auth->userId(),
                $auth->check() ? null : (string) request_value('guest_email', ''),
                $_FILES['disability_proof'] ?? null
            );
            json_success($result);

        case 'mc_pay_pending_reservation':
        case 'mc_pay_existing_reservation':
            if (!$auth->check()) {
                json_error('Niste prijavljeni.', 401);
            }
            $result = $repo->payPendingReservation(
                (int) request_value('reservation_id', 0),
                (int) $auth->userId(),
                $_FILES['disability_proof'] ?? null
            );
            json_success($result);

        case 'mc_cancel_reservation':
            if (!$auth->check()) {
                json_error('Niste prijavljeni.', 401);
            }
            $repo->cancelReservationForUser((int) request_value('id', 0), (int) $auth->userId());
            json_success('Rezervacija je otkazana.');

        case 'mc_get_reviews':
            json_success($repo->reviewStats((int) request_value('movie_id', 0)));

        case 'mc_submit_review':
            $guestEmail = $auth->check() ? null : (string) request_value('guest_email', '');
            $repo->submitReview(
                (int) request_value('movie_id', 0),
                (int) request_value('rating', 0),
                (string) request_value('comment', ''),
                $auth->userId(),
                $guestEmail
            );
            json_success('Recenzija je uspješno sačuvana.');

        case 'mc_filter_movies_html':
            $movies = $repo->filterMovies([
                'genre' => request_value('genre', ''),
                'search' => request_value('search', ''),
                'duration' => request_value('duration', ''),
                'date' => request_value('date', ''),
                'hall' => request_value('hall', ''),
            ]);

            $active = array_values(array_filter($movies, static fn(array $movie): bool => $movie['status'] === 'now_showing'));
            $soon = array_values(array_filter($movies, static fn(array $movie): bool => $movie['status'] === 'coming_soon'));
            $archived = array_values(array_filter($movies, static fn(array $movie): bool => $movie['status'] === 'archived'));

            ob_start();
            if ($active) {
                echo '<div class="mc-filter-section"><h3 class="mc-filter-title">NA PROGRAMU</h3><div class="mc-movie-grid">';
                foreach ($active as $movie) {
                    echo $repo->renderMovieCard($movie);
                }
                echo '</div></div>';
            }
            if ($soon) {
                echo '<div class="mc-filter-section"><h3 class="mc-filter-title">USKORO</h3><div class="mc-movie-grid">';
                foreach ($soon as $movie) {
                    echo $repo->renderMovieCard($movie);
                }
                echo '</div></div>';
            }
            if ($archived) {
                echo '<div class="mc-filter-section"><h3 class="mc-filter-title">RANIJE PRIKAZIVANI</h3><div class="mc-movie-grid mc-archived">';
                foreach ($archived as $movie) {
                    echo $repo->renderMovieCard($movie);
                }
                echo '</div></div>';
            }
            json_success(['html' => ob_get_clean()]);

        case 'mc_get_movies_admin':
            if (!$auth->isAdmin()) {
                json_error('Nemate pristup.', 403);
            }
            $movies = array_map(static function (array $movie): array {
                return [
                    'id' => $movie['id'],
                    'title' => $movie['title'],
                    'genres' => implode(', ', array_map(static fn(array $genre): string => $genre['name'], $movie['genres'])),
                ];
            }, $repo->movieOptionsForAdmin());
            json_success($movies);

        case 'mc_get_available_times_admin':
            if (!$auth->isAdmin()) {
                json_error('Nemate pristup.', 403);
            }
            json_success($repo->availableTimes(
                (int) request_value('movie_id', 0),
                (int) request_value('hall_id', 0),
                (string) request_value('date', '')
            ));

        case 'mc_generate_schedule':
            if (!$auth->isAdmin()) {
                json_error('Nemate pristup.', 403);
            }
            $movieIds = request_value('movies', []);
            if (!is_array($movieIds)) {
                $movieIds = request_value('movies[]', []);
            }
            $count = $repo->autoGenerateSchedule(
                (array) $movieIds,
                (string) request_value('date_from', ''),
                (string) request_value('date_to', '')
            );
            json_success(['count' => $count]);

        default:
            json_error('Nepoznata akcija.', 404);
    }
} catch (Throwable $throwable) {
    json_error($throwable->getMessage());
}
