<!DOCTYPE html>
<html>

<head>
    <title>Homeplate Change The Driver Profile Status </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>

    <b>Hi {{ ucwords(strtolower($firstName)) }} {{ ucwords(strtolower($lastName)) }}</b>

    @if ($status === 0)
        <p>Your driver account on Homeplate is now deactivated.</p>
        <p>For further inquiry, kindly mail to support@homehef.com</p>
    @elseif($status === 1)
        <p>Your driver account on Homeplate is now activated.</p>
        <p>Welcome to Homeplate. We're thrilled to have you on board!</p>
        <p>For further inquiry, kindly mail to support@homehef.com</p>
    @elseif($status === 2)
        <p>Your driver account on Homeplate is currently under review.</p>
        <p>Welcome to Homeplate. We're thrilled to have you on board!</p>
        <p>For further inquiry, kindly mail to support@homehef.com</p>
    @endif

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
