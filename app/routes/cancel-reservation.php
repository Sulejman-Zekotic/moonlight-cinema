<?php

declare(strict_types=1);

$repo = $app['repo'];
$reservationId = (int) ($_GET['rid'] ?? 0);
$token = (string) ($_GET['token'] ?? '');
$openPayment = isset($_GET['pay']) && $_GET['pay'] === '1';
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
    'openPayment' => $openPayment,
    'token' => $token,
    'styles' => ['css/site.css', 'css/reservation.css', 'css/tickets.css', 'css/admin-add-screening.css'],
    'scripts' => ['js/tickets.js'],
    'auth' => $app['auth'],
    'repo' => $repo,
    'user' => $app['auth']->user(),
], 'main');
exit;
