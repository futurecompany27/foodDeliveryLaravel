<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,
    initial-scale=1.0">
</head>

<body>
    <b>Dear {{ ucfirst($firstName) }} {{ ucfirst($lastName) }},</b>
    <br>
    <p> Thank you for your payment.</p>
    <p>
        We are pleased to confirm that Homeplate has received your <strong>CAD {{ $amount }}</strong> payment for the <strong>{{ $transaction_type }}</strong>.
        If you have any questions or need assistance, feel free to reach out to us at support@homeplate.ca
        Thank you for choosing Homeplate. We're excited to support you in your culinary journey!
    </p>
    <br>

    <p style="margin: 0px; padding: 0px">Warm regards,</p>
    <p style="margin: 0px; padding: 0px"><strong>The Homeplate Team</strong></p>
    <img src="{{ env('filePath') . 'storage/admin/new_logos/main-logo-mail.png' }}" class="object-fit-cover img-fluid" width="80"
        alt="">
</body>

</html>
