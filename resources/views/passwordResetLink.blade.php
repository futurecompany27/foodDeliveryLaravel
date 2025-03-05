<!DOCTYPE html>
<html>

<head>
    <title>Homeplate Registration </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>

    <b>Hi {{ ucfirst($firstName) }} {{ ucfirst($lastName) }}</b>

    <p>Kindly reset your {{ ucfirst($user_type) }}'s Account Password by clicking on the link below</p>

    <a class="btn btn-primary"
        href="{{ env('domain') . 'reset-password?id=' . $id . '&user_type=' . $user_type . '&token=' . $token }}">Reset
        password<a>

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
