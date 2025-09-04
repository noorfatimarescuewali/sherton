<?php
require 'db.php';
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$booking = null;
if($booking_id){
  $stmt = $mysqli->prepare("SELECT b.*, h.name AS hotel_name, h.city, h.image_url FROM bookings b JOIN hotels h ON h.id = b.hotel_id WHERE b.id = ?");
  $stmt->bind_param('i',$booking_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $booking = $res->fetch_assoc();
  $stmt->close();
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Booking Confirmation</title>
<style>
body{font-family:Inter, Arial;margin:0;background:#f8fafc;color:#222}
.wrapper{max-width:760px;margin:28px auto;padding:0 18px}
.card{background:white;border-radius:12px;padding:18px;box-shadow:0 8px 26px rgba(20,20,50,0.06)}
.header{display:flex;gap:12px;align-items:center}
.thumb{width:120px;height:80px;background-size:cover;background-position:center;border-radius:8px;flex-shrink:0}
h2{margin:0}
.small{color:#6c757d;font-size:14px}
.info{margin-top:12px}
.btn{display:inline-block;padding:10px 14px;border-radius:8px;background:#0d6efd;color:white;text-decoration:none;margin-top:12px}
</style>
</head>
<body>
  <div class="wrapper">
    <div class="card">
      <?php if(!$booking): ?>
        <h2>Booking not found</h2>
        <p class="small">We couldn't locate booking #<?=htmlspecialchars($booking_id)?>. Please contact support.</p>
      <?php else: ?>
        <div class="header">
          <div class="thumb" style="background-image:url('<?=htmlspecialchars($booking['image_url'])?>')"></div>
          <div>
            <h2>Booking Confirmed — #<?=htmlspecialchars($booking['id'])?></h2>
            <div class="small"><?=htmlspecialchars($booking['hotel_name'])?> • <?=htmlspecialchars($booking['city'])?></div>
          </div>
        </div>

        <div class="info">
          <p><strong>Guest:</strong> <?=htmlspecialchars($booking['guest_name'])?> (<?=htmlspecialchars($booking['guest_email'])?>)</p>
          <p><strong>Dates:</strong> <?=htmlspecialchars($booking['checkin'])?> to <?=htmlspecialchars($booking['checkout'])?> </p>
          <p><strong>Guests:</strong> <?=htmlspecialchars($booking['guests'])?></p>
          <p><strong>Total Paid:</strong> $<?=number_format($booking['total_price'],2)?></p>
          <p class="small">Booked on <?=htmlspecialchars($booking['created_at'])?></p>
        </div>

        <a class="btn" href="index.php">Search more hotels</a>
        <a class="btn" style="background:#6c757d;margin-left:8px" href="javascript:window.print()">Print</a>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
