<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,
    initial-scale=1.0">
    <title>{{ $subject }}</title>
    <link rel=" stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <p>Dear {{ $userName }},</p>

    <p>Great news! All your recent orders (Order ID: {{ $order_id }}) have been accepted by our talented chefs.
        They're
        busy
        preparing mouth-watering dishes just for you.</p>

    <p>Sit back, relax, and anticipate a delightful dining experience. Feel free to continue ordering and exploring our
        menu. We're here to make your culinary journey memorable.</p>

    <p>If you have any special requests, just let us know. Your satisfaction is our priority.</p>

    <p>Thanks for choosing Homeplate!</p>

    <br>
    <br>
    <br>

    <p style="margin: 0px; padding: 0px">Regards,</p>
    <p style="margin: 0px; padding: 0px">Homeplate Team</p>
    <img src="{{ env('filePath') . 'storage/admin/new_logos/main-logo-mail.png' }}" class="object-fit-cover img-fluid"
        width="80" alt="">
    <p style="margin: 0px; padding: 0px">support@homeplate.ca</p>
</body>

</html>
