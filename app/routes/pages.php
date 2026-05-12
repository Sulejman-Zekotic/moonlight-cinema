<?php

declare(strict_types=1);

$auth = $app['auth'];
$repo = $app['repo'];
$user = $auth->user();

$shared = [
    'app' => $app,
    'auth' => $auth,
    'repo' => $repo,
    'user' => $user,
];

if (str_starts_with($page, 'admin') && !$auth->isAdmin()) {
    http_response_code(403);

    return [
        'view' => 'pages/403',
        'data' => $shared + [
            'title' => 'Nemate pristup',
            'styles' => ['css/site.css'],
        ],
    ];
}

$screeningFilters = [
    'movie_id' => (string) ($_GET['movie_id'] ?? ''),
    'hall_id' => (string) ($_GET['hall_id'] ?? ''),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'search' => trim((string) ($_GET['search'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];

$screeningPage = max(1, (int) ($_GET['page'] ?? 1));
$screeningPerPage = 10;
$screeningsFiltered = $repo->screeningFilters($screeningFilters);
$screeningTotal = count($screeningsFiltered);
$screeningPageCount = max(1, (int) ceil($screeningTotal / $screeningPerPage));
$screeningPage = min($screeningPage, $screeningPageCount);
$screeningsVisible = array_slice($screeningsFiltered, ($screeningPage - 1) * $screeningPerPage, $screeningPerPage);

$resetToken = trim((string) ($_GET['token'] ?? ''));
$resetState = $auth->resetTokenState($resetToken);

return match ($page) {
    'home' => [
        'view' => 'pages/home',
        'data' => $shared + [
            'title' => 'Početna',
            'heroMovie' => $repo->heroMovie(),
            'nowShowingMovies' => $repo->nowShowingMovies(),
            'comingSoonMovies' => $repo->comingSoonMovies(),
            'topReservedMovies' => $repo->topReservedMovies(),
            'styles' => ['css/site.css'],
        ],
    ],
    'movies' => [
        'view' => 'pages/movies',
        'data' => $shared + [
            'title' => 'Filmovi',
            'genres' => $repo->genres(),
            'halls' => $repo->halls(),
            'styles' => ['css/site.css', 'css/movie-catalog.css'],
            'scripts' => ['js/movie-catalog.js'],
        ],
    ],
    'movie' => [
        'view' => 'pages/movie',
        'data' => $shared + [
            'title' => 'Detalji filma',
            'movie' => $repo->movieById((int) ($_GET['id'] ?? 0)),
            'styles' => ['css/site.css', 'css/reviews.css'],
            'scripts' => ['js/reviews.js'],
        ],
    ],
    'reservation' => [
        'view' => 'pages/reservation',
        'data' => $shared + [
            'title' => 'Rezervacija',
            'movieId' => (int) ($_GET['mid'] ?? 0),
            'styles' => ['css/site.css', 'css/reservation.css'],
            'scripts' => ['js/reservation.js'],
        ],
    ],
    'auth' => [
        'view' => 'pages/auth',
        'data' => $shared + [
            'title' => 'Prijava i registracija',
            'styles' => ['css/site.css', 'css/auth.css'],
            'scripts' => ['js/auth.js'],
        ],
    ],
    'reset-password' => [
        'view' => 'pages/reset-password',
        'data' => $shared + [
            'title' => 'Reset lozinke',
            'token' => $resetToken,
            'resetState' => $resetState,
            'styles' => ['css/site.css', 'css/auth.css'],
            'scripts' => ['js/auth.js'],
        ],
    ],
    'tickets' => [
        'view' => 'pages/tickets',
        'data' => $shared + [
            'title' => 'Moje karte',
            'tickets' => $auth->check() ? $repo->userReservations((int) $auth->userId()) : [],
            'styles' => ['css/site.css', 'css/reservation.css', 'css/tickets.css'],
            'scripts' => ['js/tickets.js'],
        ],
    ],
    'admin-hub' => [
        'view' => 'pages/admin-hub',
        'data' => $shared + [
            'title' => 'Admin panel',
            'styles' => ['css/site.css', 'css/admin-hub.css'],
        ],
    ],
    'admin-stats' => [
        'view' => 'pages/admin-stats',
        'data' => $shared + [
            'title' => 'Statistika',
            'stats' => $repo->featuredStats(),
            'styles' => ['css/site.css', 'css/admin-hub.css', 'css/admin-stats.css'],
        ],
    ],
    'admin-screenings' => [
        'view' => 'pages/admin-screenings',
        'data' => $shared + [
            'title' => 'Sve projekcije',
            'screenings' => $screeningsVisible,
            'screeningFilters' => $screeningFilters,
            'screeningPage' => $screeningPage,
            'screeningPageCount' => $screeningPageCount,
            'screeningTotal' => $screeningTotal,
            'screeningPerPage' => $screeningPerPage,
            'movies' => $repo->movieOptionsForAdmin(),
            'halls' => $repo->halls(),
            'styles' => ['css/site.css', 'css/admin-hub.css', 'css/admin-screenings.css'],
            'scripts' => ['js/admin-screenings.js'],
        ],
    ],
    'admin-add-screening' => [
        'view' => 'pages/admin-add-screening',
        'data' => $shared + [
            'title' => 'Nova projekcija',
            'movies' => $repo->movieOptionsForAdmin(),
            'halls' => $repo->halls(),
            'styles' => ['css/site.css', 'css/admin-hub.css', 'css/admin-add-screening.css'],
            'scripts' => ['js/admin-add-screening.js'],
        ],
    ],
    'admin-auto-generator' => [
        'view' => 'pages/admin-auto-generator',
        'data' => $shared + [
            'title' => 'Auto generator',
            'movies' => $repo->movieOptionsForAdmin(),
            'styles' => ['css/site.css', 'css/admin-hub.css', 'css/admin-auto-generator.css'],
            'scripts' => ['js/admin-auto-generator.js'],
        ],
    ],
    '404' => [
        'view' => 'pages/404',
        'data' => $shared + [
            'title' => 'Stranica nije pronađena',
            'styles' => ['css/site.css'],
        ],
    ],
    default => [
        'view' => 'pages/404',
        'data' => $shared + [
            'title' => 'Stranica nije pronađena',
            'styles' => ['css/site.css'],
        ],
    ],
};
