<!DOCTYPE html>
<html>

<head>
    <title>Food Certificate Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>

    <b>Hi, {{ ucfirst($firstName) }} {{ ucfirst($lastName) }}</b>

    <p>Thank you for submitting the food certificate. You will receive an email confirming the successful submission of
        the
        food certificate .</p>

    <p>The Homeplate team will verify your information and update you on the status of your application.</p>

    <p>If the home plate team finds any issue in your application then we will send you the same form link with the
        incorrect/
        missing fields.</p>

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
