<!DOCTYPE html>
<html>

<head>
    <title>Homeplate Registration </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>

    <b>Hi {{ ucfirst($firstName) }} {{ ucfirst($lastName) }}</b>
    <p>We are pleased to inform you that your account has been successfully created. However, we require verification of
        your documents and details to access your account.</p>

    <p>Please take a moment to verify your email by clicking on the link below:</p>

    <a class="btn btn-primary" href="{{ env('domain') . 'verified-mail?id=' . $id . '&type=driver' }}">Email
        Verification</a>

    <p>Thank you for choosing our services.</p>

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
