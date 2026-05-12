<?php

declare(strict_types=1);

if (!$app['auth']->check()) {
    http_response_code(403);
    exit('Niste prijavljeni.');
}

$ticket = $app['repo']->reservationTicketForUser((int) ($_GET['id'] ?? 0), (int) $app['auth']->userId());
if (!$ticket) {
    http_response_code(404);
    exit('Karta nije pronađena.');
}

require_once __DIR__ . '/../../vendor/tcpdf/tcpdf.php';

$pdf = new TCPDF();
$pdf->SetCreator('Moonlight Cinema');
$pdf->SetAuthor('Moonlight Cinema');
$pdf->SetTitle('Moonlight Cinema - Ulaznica');
$pdf->SetMargins(20, 18, 20);
$pdf->AddPage();

$html = '
<style>
body { font-family: DejaVu Sans, sans-serif; }
.ticket-wrap{ width:100%; padding:10px; }
.ticket-header{ text-align:center; margin-bottom:14px; }
.ticket-header h1{ margin:0; font-size:22px; letter-spacing:1px; }
.ticket-header small{ font-size:11px; color:#555; }
.hr{ border-top:1px solid #000; margin:12px 0 16px; }
.ticket-movie{ font-size:18px; font-weight:bold; text-align:center; margin-bottom:6px; }
.ticket-center{ text-align:center; font-size:13px; margin:4px 0; }
.ticket-seat{ font-size:16px; font-weight:bold; text-align:center; border:1px solid #000; padding:6px 0; margin:10px 0; }
.ticket-price{ text-align:center; font-size:13px; margin-bottom:8px; }
</style>
<div class="ticket-wrap">
  <div class="ticket-header">
    <h1>MOONLIGHT CINEMA</h1>
    <small>Digital Movie Ticket</small>
  </div>
  <div class="hr"></div>
  <div class="ticket-movie">' . e($ticket['title']) . '</div>
  <div class="ticket-center">' . e(format_date_local($ticket['screening_date'])) . ' • ' . e(format_time_local($ticket['screening_time'])) . '</div>
  <div class="ticket-center">Sala: ' . e($ticket['hall_name']) . '</div>
  <div class="ticket-seat">SJEDIŠTE: ' . e(implode(', ', $ticket['seat_labels'])) . '</div>
  <div class="ticket-price">Cijena: ' . e(number_format((float) $ticket['total_price'], 2)) . ' KM</div>
</div>';

$pdf->writeHTML($html, true, false, true, false, '');

$style = [
    'border' => false,
    'padding' => 2,
    'fgcolor' => [0, 0, 0],
    'bgcolor' => false,
];

$qrData = $ticket['ticket_code'];
$pageWidth = $pdf->getPageWidth();
$qrSize = 40;
$x = ($pageWidth - $qrSize) / 2;
$y = $pdf->GetY() + 6;

$pdf->write2DBarcode($qrData, 'QRCODE,M', $x, $y, $qrSize, $qrSize, $style, 'N');
$pdf->Ln(46);
$pdf->writeHTML(
    '<div style="text-align:center;font-size:10px;color:#444;">
        Rezervacija ID: ' . e($ticket['ticket_code']) . '<br>
        Molimo pokažite ovu kartu na ulazu.<br>
        Karta vrijedi samo za navedeni termin.
    </div>',
    true,
    false,
    true,
    false,
    ''
);

$pdf->Output('moonlight-ticket-' . $ticket['id'] . '.pdf', 'D');
exit;
