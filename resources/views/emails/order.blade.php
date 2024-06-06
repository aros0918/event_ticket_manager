<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Order Confirmation</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            padding: 20px;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }
        .header {
            text-align: center;
        }
        .header h1 {
            margin-bottom: 10px;
            color: #4CAF50;
        }
        .order-details {
            margin: 20px 0;
        }
        .order-details p {
            font-size: 16px;
            margin: 5px 0;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        .tickets {
            margin: 20px 0;
        }
        .tickets p {
            font-size: 16px;
            margin: 5px 0;
        }
        .icon {
            width: 20px;
            vertical-align: middle;
            margin-right: 8px;
        }
        .download-btn {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 10px;
            font-size: 16px;
            color: #fff;
            background-color: #4CAF50;
            border: none;
            border-radius: 5px;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Order Confirmation</h1>
            <p>Thank you for your order!</p>
        </div>
   
        <div class="order-details">
            <p><img class="icon" src="https://cdn-icons-png.flaticon.com/512/126/126486.png" alt="Order Icon"> Order Number: <?= $order_number ?></p>
            <p><img class="icon" src="https://cdn-icons-png.flaticon.com/512/481/481625.png" alt="Calendar Icon"> Booking Time: <?= $booking_time ?></p>
            <p><img class="icon" src="https://cdn-icons-png.flaticon.com/512/561/561127.png" alt="Email Icon"> Customer Email: <?= $customer_email ?></p>
        </div>

        <div class="tickets">
            <p>General Tickets: <?= $quantity_general ?></p>
            <p>VIP Tickets: <?= $quantity_vip ?></p>
        </div>

        <div class="qr-code">
            <p>Scan your QR Code: <?= $order_number ?></p>
            <img src="<?= $qr_code ?>" alt="QR Code">
            <br>
            <a href="<?= $qr_code ?>" download="fugaz_<?= $order_number ?>.png" class="download-btn">Download QR Code</a>
        </div>
    </div>
</body>

</html>
