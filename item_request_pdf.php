<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/pdf_helper.php';

$auth->requireLogin();
$user = $auth->getCurrentUser();
$db = Database::getInstance();

$id = (int)($_GET['id'] ?? 0);
$req = $db->fetchOne("
    SELECT r.*, 
           ua.full_name AS approved_name,
           ur.full_name AS rejected_name,
           uc.full_name AS creator_name
    FROM item_requests r
    LEFT JOIN users ua ON r.approved_by = ua.id
    LEFT JOIN users ur ON r.rejected_by = ur.id
    LEFT JOIN users uc ON r.created_by = uc.id
    WHERE r.id = ?
", [$id]);

if (!$req) {
    http_response_code(404);
    die('Data pengajuan tidak ditemukan.');
}

$isAdmin = in_array((int)$user['role_id'], [1, 2], true);
$isOwner = (int)$req['created_by'] === (int)$user['id'];
if ($req['status'] === 'pending' && !$isAdmin && !$isOwner) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    die('<div style="font-family:sans-serif;background:#fef3c7;color:#92400e;padding:20px;border-radius:8px;max-width:600px;margin:40px auto;border:1px solid #fcd34d"><strong>Belum Bisa Dijadikan PDF:</strong> Pengajuan <code style="background:#fde68a;padding:2px 6px;border-radius:4px">' . htmlspecialchars($req['request_no']) . '</code> masih status <strong>MENUNGGU APPROVAL</strong>. Fitur download PDF hanya aktif setelah pengajuan disetujui/ditolak oleh Admin.</div>');
}

$items = json_decode($req['item_details'], true) ?: [];

function rupiah($v) {
    return 'Rp ' . number_format($v, 0, ',', '.');
}
function idDate($s) {
    if (!$s) return '-';
    $d = new DateTime($s);
    $bln = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    return $d->format('d') . ' ' . $bln[(int)$d->format('m')-1] . ' ' . $d->format('Y') . ' ' . $d->format('H:i');
}
function idDateShort($s) {
    if (!$s) return '-';
    $d = new DateTime($s);
    $bln = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    return $d->format('d') . ' ' . $bln[(int)$d->format('m')-1] . ' ' . $d->format('Y');
}

$pdf = new SimplePDF();
$pdf->AddPage();

$maxX = 555;
$marginL = 40;

$pdf->SetFont('Helvetica','B',18);
$pdf->Cell(475, 14, 'JAWIL GROUP', 0, 0, 'L');
$pdf->Ln();
$pdf->SetFont('Helvetica','',10);
$pdf->Cell(475, 6, 'Sistem Manajemen Inventaris & Pengadaan Barang', 0, 0, 'L');
$pdf->Ln(10);
$pdf->Line($marginL, $pdf->y - 2, $maxX, $pdf->y - 2);
$pdf->Line($marginL, $pdf->y + 1, $maxX, $pdf->y + 1);
$pdf->Ln(12);

$pdf->SetFont('Helvetica','B',15);
$pdf->Cell(515, 10, 'FORMULIR PENGAJUAN BARANG', 0, 2, 'C');
$pdf->SetFont('Helvetica','',10);
$pdf->Cell(515, 6, 'No. Dokumen: ' . $req['request_no'] . '   |   Status: ' . strtoupper($req['status']), 0, 2, 'C');
$pdf->Ln(8);

$pdf->SetFont('Helvetica','B',10);
$pdf->Cell(130, 7, 'TANGGAL PENGAJUAN', 'LTB', 0, 'L');
$pdf->SetFont('Helvetica','',10);
$pdf->Cell(130, 7, ':  ' . idDateShort($req['request_date']), 'TB', 0, 'L');
$pdf->SetFont('Helvetica','B',10);
$pdf->Cell(130, 7, 'DEPARTEMEN', 'TB', 0, 'L');
$pdf->SetFont('Helvetica','',10);
$pdf->Cell(125, 7, ':  ' . ($req['department'] ?: '-'), 'RTB', 1, 'L');

$pdf->SetFont('Helvetica','B',10);
$pdf->Cell(130, 7, 'NAMA PEMOHON', 'LTB', 0, 'L');
$pdf->SetFont('Helvetica','',10);
$pdf->Cell(130, 7, ':  ' . $req['requester_name'], 'TB', 0, 'L');
$pdf->SetFont('Helvetica','B',10);
$pdf->Cell(130, 7, 'KODE PEGAWAI', 'TB', 0, 'L');
$pdf->SetFont('Helvetica','',10);
$pdf->Cell(125, 7, ':  ' . ($req['employee_id'] ?: '-'), 'RTB', 1, 'L');

$pdf->SetFont('Helvetica','B',10);
$pdf->Cell(130, 7, 'DIBUAT OLEH', 'LTB', 0, 'L');
$pdf->SetFont('Helvetica','',10);
$pdf->Cell(130, 7, ':  ' . ($req['creator_name'] ?: '-'), 'TB', 0, 'L');
$pdf->Cell(130, 7, '', 'TB', 0, 'L');
$pdf->Cell(125, 7, '', 'RTB', 1, 'L');
$pdf->Ln(8);

$pdf->SetFont('Helvetica','B',10);
$pdf->Cell(515, 7, 'KEPERLUAN / TUJUAN PENGAJUAN', 'LTR', 1, 'L');
$pdf->SetFont('Helvetica','',10);
$purposeTxt = $req['purpose'] ?: 'Tidak ada keterangan.';
$pdf->MultiCell(515, 6, '  ' . $purposeTxt, 'LR', 'L');
$pdf->Cell(515, 1, '', 'LRB', 1);
$pdf->Ln(8);

$pdf->SetFont('Helvetica','B',10);
$wCol = [28, 130, 125, 45, 42, 70, 75];
$head = ['NO','NAMA BARANG','SPESIFIKASI / CATATAN','QTY','SATUAN','HARGA SATUAN','SUB TOTAL'];
$aligns = ['C','L','L','C','C','R','R'];
foreach ($head as $i => $h) {
    $pdf->Cell($wCol[$i], 8, $h, 1, $i===count($head)-1 ? 1 : 0, $aligns[$i], true);
}
$pdf->SetFont('Helvetica','',9);
$no = 1; $totalQty = 0; $totalAmt = 0;
foreach ($items as $it) {
    $q = (int)($it['qty'] ?? 0);
    $p = (float)($it['price'] ?? 0);
    $s = $q * $p;
    $totalQty += $q;
    $totalAmt += $s;
    $pdf->Cell($wCol[0], 7, (string)$no++, 'LR', 0, 'C');
    $pdf->Cell($wCol[1], 7, $it['name'] ?? '', 'LR', 0, 'L');
    $pdf->Cell($wCol[2], 7, $it['note'] ?? '-', 'LR', 0, 'L');
    $pdf->Cell($wCol[3], 7, (string)$q, 'LR', 0, 'C');
    $pdf->Cell($wCol[4], 7, $it['unit'] ?? 'pcs', 'LR', 0, 'C');
    $pdf->Cell($wCol[5], 7, $p ? rupiah($p) : '-', 'LR', 0, 'R');
    $pdf->Cell($wCol[6], 7, $p ? rupiah($s) : '-', 'LR', 1, 'R');
}
if (empty($items)) {
    $pdf->Cell(515, 8, 'Tidak ada item dalam pengajuan.', 'LR', 1, 'C');
}
$pdf->SetFont('Helvetica','B',10);
$pdf->Cell(array_sum(array_slice($wCol,0,3)), 9, 'TOTAL', 'LRT', 0, 'R');
$pdf->Cell($wCol[3], 9, (string)$totalQty, 'TR', 0, 'C');
$pdf->Cell($wCol[4], 9, 'unit', 'T', 0, 'C');
$pdf->Cell($wCol[5], 9, 'Nilai Pengajuan', 'TR', 0, 'R');
$pdf->Cell($wCol[6], 9, rupiah($totalAmt), 'LRTB', 1, 'R');
$pdf->Ln(8);

if ($req['admin_note']) {
    $pdf->SetFont('Helvetica','B',10);
    $pdf->Cell(515, 7, 'CATATAN ADMINISTRATOR', 'LTR', 1, 'L');
    $pdf->SetFont('Helvetica','',10);
    $pdf->MultiCell(515, 6, '  ' . $req['admin_note'], 'LR', 'L');
    $pdf->Cell(515, 1, '', 'LRB', 1);
    $pdf->Ln(6);
}

$pdf->Ln(10);
$signW = 515 / 3;
$pdf->SetFont('Helvetica','',10);
$labels = ['Pemohon,', 'Mengetahui Admin / Approval,', 'Penerima Gudang,'];
foreach ($labels as $i => $lbl) {
    $pdf->Cell($signW, 6, $lbl, 0, $i===2?1:0, 'C');
}
$pdf->Ln(36);
$names = [
    $req['requester_name'],
    $req['status']==='approved' ? ($req['approved_name'] ?? 'Administrator') : ($req['status']==='rejected' ? ($req['rejected_name'] ?? 'Administrator') : '____________________'),
    '____________________'
];
$dates = [
    '',
    $req['status']==='approved' ? idDate($req['approved_at']) : ($req['status']==='rejected' ? idDate($req['rejected_at']) : ''),
    ''
];
$pdf->SetFont('Helvetica','B',10);
foreach ($names as $i => $nm) {
    $pdf->Cell($signW, 6, $nm, 0, $i===2?1:0, 'C');
}
$pdf->SetFont('Helvetica','',8);
foreach ($dates as $i => $dt) {
    $pdf->Cell($signW, 5, $dt, 0, $i===2?1:0, 'C');
}
$pdf->Ln(30);
$pdf->SetFont('Helvetica','',8);
$pdf->Cell(515, 5, 'Dicetak otomatis oleh Sistem Inventaris Jawil Group pada ' . idDate(date('Y-m-d H:i:s')), 0, 1, 'C');

$filename = 'Pengajuan_Barang_' . str_replace(['/',' '], '_', $req['request_no']) . '.pdf';
$download = ($_GET['download'] ?? '') === '1' ? 'D' : 'I';
$pdf->Output($filename, $download);
?>
