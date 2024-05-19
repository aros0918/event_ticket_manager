<!-- resources/views/emails/order.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>QR Code Generator with Download</title>

</head>
<body>
    <h1>Order Confirmation</h1>
    <p>Thank you for your order!</p>
    <div class="visible-print text-center">
    <div class="container text-center">
    <div class="visible-print text-center">
        <div class="container text-center">
            <div class="row">
                <div class="col-md-2">
                    <p class="mb-0">Simple</p>
                    <img src="{{ $simple }}" alt="QR Code">
                </div>
               
            </div>
        </div>
       
</div>
</body>
</html>
