<?php
require('../fpdf/fpdf.php'); // Correct path to the FPDF library
include 'db_connection.php';

// Get data for the PDF
$transactions = json_decode($_POST['transactions'], true);

// Create a new FPDF instance
$pdf = new FPDF();
$pdf->AddPage();

// Set title
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(190, 10, 'Transaction Report', 0, 1, 'C');
$pdf->Ln(10);

// Set table headers
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 10, 'Date', 1);
$pdf->Cell(50, 10, 'Description', 1);
$pdf->Cell(30, 10, 'Debit', 1);
$pdf->Cell(30, 10, 'Credit', 1);
$pdf->Cell(40, 10, 'Balance', 1);
$pdf->Ln();

// Output each transaction row
$pdf->SetFont('Arial', '', 12);
foreach ($transactions as $transaction) {
    $pdf->Cell(40, 10, $transaction['transaction_date'], 1);
    $pdf->Cell(50, 10, $transaction['transaction_description'], 1);
    $pdf->Cell(30, 10, $transaction['debit'], 1);
    $pdf->Cell(30, 10, $transaction['credit'], 1);
    $pdf->Cell(40, 10, $transaction['balance'], 1);
    $pdf->Ln();
}


// Output the PDF
$pdf->Output();
