<!DOCTYPE html>
<html>

<head>
    <title>Chef Is Taxable </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <b>Hi {{ ucfirst($firstName) }} {{ ucfirst($lastName) }}</b>

    <br>

    <p>Your earnings for this month have exceeded the limit of 30,000 CA$. As a non-taxable chef, your account has been
        temporarily deactivated.</p>

    <p>Total earnings this month: {{ number_format($totalEarnings, 2) }} CA$</p>

    <p>Please contact us for further assistance.</p>

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
