<?php

declare(strict_types=1);

$repo = $app['repo'];
$reservationId = (int) ($_GET['rid'] ?? 0);
$token = (string) ($_GET['token'] ?? '');
$message = null;
$error = null;

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
    'styles' => ['css/site.css', 'css/tickets.css', 'css/admin-add-screening.css'],
    'auth' => $app['auth'],
    'repo' => $repo,
    'user' => $app['auth']->user(),
], 'main');
exit;
