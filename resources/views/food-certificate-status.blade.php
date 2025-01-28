<!DOCTYPE html>
<html>

<head>
    <title>Food Certificate Status Changed </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="shadow p-3 mb-5 bg-body-tertiary rounded">

        <b>Hi {{ ucfirst($chefDetail->firstName) }} {{ ucfirst($chefDetail->lastName) }}</b>
        <br>
        <p>We hope this email finds you well.</p>
        <p>We are reaching out to remind you about the activation link for the food certificate form in your chef
            dashboard.
        </p>
        <p>To login to your dashboard, you can click on the following</p>
        <a href="http://homeplate.ca/login-register?tab=login">Click Here to Login</a>
        <p>It's crucial to fill out this form with all your accurate details and submit it to us promptly. Once we
            receive
            your
            submission, we will process it and submit the form to the government within 5 working days.

            In case the Homeplate team identifies any missing information, we will reach out to you for updates.</p>

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
