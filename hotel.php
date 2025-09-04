<?php
require 'db.php';

$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
$hotel = null;

if($hotel_id){
    $stmt = $mysqli->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt->bind_param('i',$hotel_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $hotel = $res->fetch_assoc();
    $stmt->close();
}

$error = '';
$successBookingId = null;

// Handle booking POST
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])){
    $h_id = (int)$_POST['hotel_id'];
    $guest_name = trim($_POST['guest_name']);
    $guest_email = trim($_POST['guest_email']);
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    $guests = max(1, (int)$_POST['guests']);

    if(!$guest_name || !$guest_email || !$checkin || !$checkout){
        $error = 'Please complete all booking fields.';
    } else {
        // compute nights
        $d1 = new DateTime($checkin);
        $d2 = new DateTime($checkout);
        $interval = $d1->diff($d2);
        $nights = max(1, $interval->days);
        // fetch price
        $stmt = $mysqli->prepare("SELECT price_per_night FROM hotels WHERE id = ?");
        $stmt->bind_param('i', $h_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        $price = $row ? (float)$row['price_per_night'] : 0;
        $total = round($price * $nights, 2);

        // insert booking
        $ins = $mysqli->prepare("INSERT INTO bookings (hotel_id, guest_name, guest_email, checkin, checkout, guests, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param('issssid', $h_id, $guest_name, $guest_email, $checkin, $checkout, $guests, $total);
        if($ins->execute()){
            $successBookingId = $ins->insert_id;
        } else {
            $error = 'Failed to save booking. Please try again.';
        }
        $ins->close();
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title><?= $hotel ? htmlspecialchars($hotel['name']) : 'Hotel' ?> — Details</title>
<style>
body{font-family:Inter, Arial;margin:0;background:#fff;color:#222}
.wrapper{max-width:1100px;margin:18px auto;padding:0 18px}
.hero{height:360px;border-radius:12px;background-size:cover;background-position:center;box-shadow:0 10px 30px rgba(10,10,30,0.06)}
.content{display:grid;grid-template-columns:1fr 360px;gap:18px;margin-top:16px}
.card{background:white;padding:14px;border-radius:12px;box-shadow:0 6px 18px rgba(20,20,50,0.04)}
h1{margin:0 0 6px}
.small{font-size:13px;color:#6c757d}
.booking-form input, .booking-form select, .booking-form button{width:100%;padding:10px;margin-bottom:8px;border:1px solid #eee;border-radius:8px;font-size:14px}
.booking-form button{background:#0d6efd;color:white;border:0;cursor:pointer}
.meta p{margin:8px 0}
@media (max-width:880px){ .content{grid-template-columns:1fr} .hero{height:220px} }
.alert{padding:10px;border-radius:8px;margin-bottom:8px}
.alert.error{background:#ffe9e9;color:#7a1515}
.alert.success{background:#e9ffef;color:#0b6b2f}
</style>
</head>
<body>
  <div class="wrapper">
    <?php if(!$hotel): ?>
      <div class="card"><h2>Hotel not found</h2><p class="small">Please go back and select another hotel.</p></div>
    <?php else: ?>
      <div class="hero" style="background-image:url('<?=htmlspecialchars($hotel['image_url'])?>')"></div>

      <div class="content">
        <div class="card">
          <h1><?=htmlspecialchars($hotel['name'])?></h1>
          <div class="small"><?=htmlspecialchars($hotel['city'])?> • <?=htmlspecialchars($hotel['type'])?> • Rating <?=htmlspecialchars($hotel['rating'])?></div>

          <div style="margin-top:12px" class="meta">
            <p><?=nl2br(htmlspecialchars($hotel['description']))?></p>
            <p class="small">Amenities: <?=htmlspecialchars($hotel['amenities'])?></p>
            <p class="small">Price per night: <strong>$<?=number_format($hotel['price_per_night'],2)?></strong></p>
          </div>
        </div>

        <aside class="card">
          <h3 style="margin-top:0">Book your stay</h3>

          <?php if($error): ?>
            <div class="alert error"><?=htmlspecialchars($error)?></div>
          <?php endif; ?>

          <?php if($successBookingId): ?>
            <div class="alert success">Booking confirmed! Redirecting...</div>
            <script>
              // redirect (JS-only) to confirmation page with booking_id
              setTimeout(function(){
                window.location.href = 'confirm.php?booking_id=<?= (int)$successBookingId ?>';
              }, 800);
            </script>
          <?php else: ?>
            <form class="booking-form" method="post" onsubmit="return validateForm()">
              <input type="hidden" name="hotel_id" value="<?= (int)$hotel['id'] ?>" />
              <label>Name</label>
              <input name="guest_name" required />
              <label>Email</label>
              <input name="guest_email" type="email" required />
              <label>Check-in</label>
              <input name="checkin" type="date" required value="<?= htmlspecialchars(isset($_GET['checkin'])?$_GET['checkin']:'') ?>" />
              <label>Check-out</label>
              <input name="checkout" type="date" required value="<?= htmlspecialchars(isset($_GET['checkout'])?$_GET['checkout']:'') ?>" />
              <label>Guests</label>
              <select name="guests">
                <option>1</option><option>2</option><option>3</option><option>4</option>
              </select>
              <input type="hidden" name="book" value="1" />
              <button type="submit">Reserve — $<?=number_format($hotel['price_per_night'],2)?> / night</button>
            </form>
          <?php endif; ?>
        </aside>
      </div>
    <?php endif; ?>
  </div>

<script>
function validateForm(){
  const f = document.querySelector('.booking-form');
  const ci = f.checkin.value;
  const co = f.checkout.value;
  if(!ci || !co){ alert('Please select check-in and check-out dates'); return false; }
  if(new Date(ci) >= new Date(co)){ alert('Check-out must be after check-in'); return false; }
  return true;
}
</script>
</body>
</html>
