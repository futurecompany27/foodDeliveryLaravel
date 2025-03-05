<!DOCTYPE html>
<html>

<head>
    <title>Your OTP for Verification </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="shadow p-3 mb-5 bg-body-tertiary rounded">
        <p>Hi, {{ ucfirst($firstName) }} {{ ucfirst($lastName) }}</p>
        <br>
        <p>Your OTP code is: <strong class="text-danger"> {{ $otp}}</strong></p>

        <p>Please use this OTP to complete your verification within the next 5 minutes. Do not share this OTP with anyone.</p>
        <p>Thank you for using our service!</p>
        <br>
        <br>
        <br>

        <p style="margin: 0px; padding: 0px">Regards,</p>
        <p style="margin: 0px; padding: 0px">Homeplate Team</p>
        <img src="{{ env('filePath') . 'storage/admin/new_logos/main-logo-mail.png' }}" class="object-fit-cover img-fluid"
            width="80" alt="">
        <p style="margin: 0px; padding: 0px">support@homeplate.ca</p>

    </div>
</body>

</html>
