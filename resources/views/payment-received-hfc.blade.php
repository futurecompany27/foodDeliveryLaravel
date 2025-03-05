<!DOCTYPE html>
<html>

<head>
    <title> Payment Received to Homeplate </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>

    <b>Hi {{ ucfirst($firstName) }} {{ ucfirst($lastName) }}</b>

    <br>
    <p>We are pleased to inform you that a payment has been received for Homeplate.</p>
    <strong>Transaction ID: {{ $transaction_id }}</strong>
    <br>
    <strong>Amount: {{ $transaction_amount }}</strong>
    <p>Thank you for your continued dedication and hard work</p>

    <br>
    <br>
    <br>

    <p style="margin: 0px; padding: 0px">Regards,</p>
    <p style="margin: 0px; padding: 0px">Homeplate Team</p>
    <img src="{{ env('filePath') . 'storage/admin/new_logos/main-logo-mail.png' }}" class="object-fit-cover img-fluid" width="80"
        alt="">
    <p style="margin: 0px; padding: 0px">support@homeplate.ca</p>
</body>

</html>
