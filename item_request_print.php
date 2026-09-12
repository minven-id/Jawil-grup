<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

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
    die('<div style="font-family:sans-serif;background:#fee2e2;color:#991b1b;padding:20px;border-radius:8px;max-width:500px;margin:40px auto;border:1px solid #fca5a5"><strong>404 Tidak Ditemukan:</strong> Data pengajuan tidak ada.</div>');
}

$isAdmin = in_array((int)$user['role_id'], [1, 2], true);
$isOwner = (int)$req['created_by'] === (int)$user['id'];
if ($req['status'] === 'pending' && !$isAdmin && !$isOwner) {
    http_response_code(403);
    die('<div style="font-family:sans-serif;background:#fef3c7;color:#92400e;padding:20px;border-radius:8px;max-width:600px;margin:40px auto;border:1px solid #fcd34d"><strong>Belum Bisa Dicetak:</strong> Pengajuan <code style="background:#fde68a;padding:2px 6px;border-radius:4px">' . htmlspecialchars($req['request_no']) . '</code> masih status <strong>MENUNGGU APPROVAL</strong>. Cetak/PDF hanya tersedia setelah pengajuan disetujui atau ditolak Admin.<br><br><em>Pengecualian: Admin & pembuat pengajuan dapat melihat preview untuk memeriksa detail, namun tombol di daftar tetap disabled untuk user lain.</em></div>');
}

$items = json_decode($req['item_details'], true) ?: [];

function statusText($s) {
    if ($s === 'approved') return 'DISETUJUI';
    if ($s === 'rejected') return 'DITOLAK';
    return 'MENUNGGU APPROVAL';
}
function statusColor($s) {
    if ($s === 'approved') return '#16a34a';
    if ($s === 'rejected') return '#dc2626';
    return '#ca8a04';
}
$tglDok = (new DateTime($req['request_date']))->format('d M Y');
$now = date('d M Y H:i');
$approvalAt = $req['approved_at'] ? (new DateTime($req['approved_at']))->format('d M Y H:i') : null;
$rejectAt = $req['rejected_at'] ? (new DateTime($req['rejected_at']))->format('d M Y H:i') : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak - Pengajuan <?= htmlspecialchars($req['request_no']) ?></title>
<style>
  * { box-sizing: border-box; }
  body { font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; color: #111; background: #fff; }
  .page { max-width: 900px; margin: 0 auto; padding: 32px 40px; }
  .kop { border-bottom: 3px double #1e3a8a; padding-bottom: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 20px; }
  .logo { width: 64px; height: 64px; border-radius: 12px; background: linear-gradient(135deg,#4f46e5,#9333ea); display:flex; align-items:center; justify-content:center; color:white; font-size: 30px; font-weight:bold; }
  .kop h1 { font-size: 22px; margin: 0; color: #1e3a8a; letter-spacing: .5px; }
  .kop p { margin: 2px 0; font-size: 12px; color: #475569; }
  .judul { text-align: center; margin: 18px 0 6px; font-size: 20px; font-weight: 800; letter-spacing: .5px; color: #0f172a; text-transform: uppercase; }
  .subjudul { text-align: center; color: #475569; font-size: 12px; margin-bottom: 22px; }
  .grid-top { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 40px; margin-bottom: 18px; font-size: 13px; }
  .grid-top .lbl { display: inline-block; width: 120px; color: #475569; }
  .grid-top .val { color: #0f172a; font-weight: 600; }
  .status-badge { display: inline-block; padding: 5px 14px; border-radius: 20px; font-weight: 800; font-size: 12px; letter-spacing: .3px; color: #fff; }
  table.w { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
  table.w th { background: #1e3a8a; color: #fff; padding: 10px 12px; text-align: left; font-weight: 700; }
  table.w td { padding: 9px 12px; border: 1px solid #cbd5e1; vertical-align: top; }
  table.w .no { width: 40px; text-align: center; }
  table.w .qty, table.w .price, table.w .sub { text-align: right; white-space: nowrap; }
  table.w tbody tr:nth-child(even) td { background: #f8fafc; }
  table.w tfoot td { background: #f1f5f9; font-weight: 700; border-top: 2px solid #1e3a8a; }
  .purpose { border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; background: #f8fafc; font-size: 13px; margin-bottom: 18px; }
  .purpose h5 { margin: 0 0 4px; color: #1e3a8a; font-size: 12px; text-transform: uppercase; }
  .signs { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-top: 40px; font-size: 13px; }
  .sign-box { text-align: center; }
  .sign-box .label { font-weight: 600; color: #334155; margin-bottom: 72px; }
  .sign-box .name { border-top: 1px dashed #94a3b8; padding-top: 6px; color: #0f172a; font-weight: 700; }
  .note-box { margin-top: 16px; border-left: 4px solid #1e3a8a; background: #eff6ff; padding: 10px 14px; border-radius: 4px; font-size: 13px; }
  .note-box h5 { margin: 0 0 4px; color: #1e40af; font-size: 12px; text-transform: uppercase; }
  .foot { margin-top: 28px; text-align: center; color: #94a3b8; font-size: 11px; }
  .actions { text-align: center; padding: 16px 0; position: sticky; top: 0; background: #f1f5f9; border-bottom: 1px solid #cbd5e1; z-index: 10; }
  .actions button { background: #1e3a8a; color: #fff; padding: 10px 22px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin: 0 4px; }
  .actions button.pdf { background: #dc2626; }
  .actions button:hover { opacity: .9; }
  @media print {
    .actions { display: none; }
    body { background: #fff; }
    .page { padding: 8mm 10mm; }
    @page { size: A4; margin: 8mm; }
  }
</style>
</head>
<body>
<div class="actions">
    <button onclick="window.print()"><i class="fas fa-print"></i> Cetak Dokumen</button>
    <button class="pdf" onclick="location.href='item_request_pdf.php?id=<?= $req['id'] ?>'"><i class="fas fa-file-pdf"></i> Download PDF</button>
    <button onclick="history.back()"><i class="fas fa-arrow-left"></i> Kembali</button>
</div>
<div class="page">
  <div class="kop">
    <div class="logo">J</div>
    <div>
      <h1>JAWIL GROUP</h1>
      <p>Sistem Manajemen Inventaris & Pengadaan Barang</p>
      <p>Dokumen ini dicetak pada: <?= $now ?></p>
    </div>
  </div>

  <div class="judul">Formulir Pengajuan Barang</div>
  <div class="subjudul">Nomor Dokumen: <strong style="color:#1e3a8a" class="font-mono"><?= htmlspecialchars($req['request_no']) ?></strong> &nbsp; • &nbsp; Status:
    <span class="status-badge" style="background:<?= statusColor($req['status']) ?>"><?= statusText($req['status']) ?></span>
  </div>

  <div class="grid-top">
    <div><span class="lbl">Tanggal Pengajuan</span><span class="val">: <?= $tglDok ?></span></div>
    <div><span class="lbl">Departemen / Bagian</span><span class="val">: <?= htmlspecialchars($req['department'] ?: '-') ?></span></div>
    <div><span class="lbl">Nama Pemohon</span><span class="val">: <?= htmlspecialchars($req['requester_name']) ?></span></div>
    <div><span class="lbl">Kode Pegawai</span><span class="val">: <?= htmlspecialchars($req['employee_id'] ?: '-') ?></span></div>
    <div><span class="lbl">Dibuat Oleh</span><span class="val">: <?= htmlspecialchars($req['creator_name'] ?: '-') ?></span></div>
  </div>

  <div class="purpose">
    <h5><i class="fas fa-flag"></i> Keperluan / Tujuan Pengajuan</h5>
    <?= nl2br(htmlspecialchars($req['purpose'] ?: 'Tidak ada keterangan.')) ?>
  </div>

  <table class="w">
    <thead>
      <tr>
        <th class="no">No</th>
        <th>Nama Barang</th>
        <th>Spesifikasi / Catatan</th>
        <th>Qty</th>
        <th>Satuan</th>
        <th class="price">Est. Harga Satuan</th>
        <th class="sub">Sub Total</th>
      </tr>
    </thead>
    <tbody>
      <?php $totalQty = 0; $totalAmt = 0; $no = 1;
        foreach ($items as $it) {
            $q = (int)($it['qty'] ?? 0);
            $p = (float)($it['price'] ?? 0);
            $s = $q * $p;
            $totalQty += $q;
            $totalAmt += $s;
      ?>
      <tr>
        <td class="no"><?= $no++ ?></td>
        <td style="font-weight:600"><?= htmlspecialchars($it['name'] ?? '') ?></td>
        <td style="color:#475569"><?= htmlspecialchars($it['note'] ?? '-') ?></td>
        <td class="qty"><?= $q ?></td>
        <td><?= htmlspecialchars($it['unit'] ?? 'pcs') ?></td>
        <td class="price"><?= $p ? 'Rp ' . number_format($p, 0, ',', '.') : '-' ?></td>
        <td class="sub"><?= $p ? 'Rp ' . number_format($s, 0, ',', '.') : '-' ?></td>
      </tr>
      <?php } ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3" style="text-align:right">TOTAL SEMUA</td>
        <td class="qty" style="font-size:15px;color:#1e3a8a"><?= $totalQty ?></td>
        <td>unit</td>
        <td class="price">Nilai Pengajuan</td>
        <td class="sub" style="font-size:15px;color:#1e3a8a">Rp <?= number_format($totalAmt, 0, ',', '.') ?></td>
      </tr>
    </tfoot>
  </table>

  <?php if ($req['admin_note']): ?>
  <div class="note-box">
    <h5><i class="fas fa-comment-dots"></i> Catatan Administrator</h5>
    <?= nl2br(htmlspecialchars($req['admin_note'])) ?>
  </div>
  <?php endif; ?>

  <div class="signs">
    <div class="sign-box">
      <div class="label">Pemohon,</div>
      <div class="name"><?= htmlspecialchars($req['requester_name']) ?></div>
    </div>
    <div class="sign-box">
      <div class="label">
        <?= $req['status']==='rejected' ? 'Ditolak Oleh,' : ($req['status']==='approved' ? 'Disetujui Oleh,' : 'Mengetahui Admin,') ?>
      </div>
      <div class="name">
        <?php
          if ($req['status']==='approved') echo htmlspecialchars($req['approved_name'] ?? 'Administrator'). ($approvalAt ? "<br><small style='color:#475569;font-weight:400'>$approvalAt</small>" : '');
          elseif ($req['status']==='rejected') echo htmlspecialchars($req['rejected_name'] ?? 'Administrator') . ($rejectAt ? "<br><small style='color:#475569;font-weight:400'>$rejectAt</small>" : '');
          else echo '_____________________';
        ?>
      </div>
    </div>
    <div class="sign-box">
      <div class="label">Gudang / Penerima,</div>
      <div class="name">_____________________</div>
    </div>
  </div>

  <div class="foot">
    Dokumen ini dihasilkan otomatis oleh Sistem Inventaris Jawil Group • Halaman 1/1
  </div>
</div>
</body>
</html>
