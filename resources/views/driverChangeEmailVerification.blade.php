<!DOCTYPE html>
<html>

<head>
    <title>Homeplate Driver Registration </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>


    <b>Hi {{ ucfirst($firstName) }} {{ ucfirst($lastName) }}</b>
    <p>Your email address has been updated successfully.</p>
    <p>You may access your account only after the verification of your documents and details.</p>

    <p>Kindly verify your email by clicking on the link below</p>

    <a class="btn btn-primary" href="{{ env('domain') . 'verified-mail?id=' . $id . '&type=driver' }}">Email
        Verification<a>

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
