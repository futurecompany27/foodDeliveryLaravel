<!DOCTYPE html>
<html>

<head>
    <title>Home Shef Registration </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>


    <b>Hello {{ ucwords($full_name) }},</b>
    <p>Congratulations ! Your dish - <b>{{ $food_name }}</b> has been approved by our Admin. You can
        check the same on the website. You can edit the details anytime you want but will have to wait till the admin
        approves it again. It wont take us much time though.

        Hope you have a pleasant time.</p>

    <p>Regards,</p>
    <p>Homeshef Team</p>
</body>

</html>