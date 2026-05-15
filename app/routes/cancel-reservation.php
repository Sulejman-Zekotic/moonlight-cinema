<?php

declare(strict_types=1);

$repo = $app['repo'];
$reservationId = (int) ($_GET['rid'] ?? 0);
$token = (string) ($_GET['token'] ?? '');
$message = null;
$error = null;
$mode = (string) ($_GET['mode'] ?? 'cancel');

if ($reservationId && $token && isset($_GET['confirm']) && $_GET['confirm'] === '1') {
    try {
        $repo->cancelReservationForGuest($reservationId, $token);
        $message = 'Rezervacija je uspješno otkazana.';
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$reservation = $repo->reservationForCancelPage($reservationId, $token);

render_view('pages/cancel-reservation', [
    'title' => 'Otkazivanje rezervacije',
    'reservation' => $reservation,
    'message' => $message,
    'error' => $error,
    'mode' => $mode,
    'styles' => ['css/site.css', 'css/tickets.css', 'css/reservation.css', 'css/admin-add-screening.css'],
    'scripts' => ['js/cancel-reservation.js'],
    'auth' => $app['auth'],
    'repo' => $repo,
    'user' => $app['auth']->user(),
], 'main');
exit;
