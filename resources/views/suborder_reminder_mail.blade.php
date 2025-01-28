<!DOCTYPE html>
<html>
<head>
    <title>Suborder Status Notification</title>
</head>
<body>
    <p><strong>Dear {{ ucfirst($suborder->chefs->firstName).' '. ucfirst($suborder->chefs->lastName)}},</strong></p>
    <p>A new order {{ $suborder->sub_order_id }} has been placed and requires your acceptance to proceed.
        Please review the order and accept it if you can fulfill it.</p>
    <br>
    <p style="margin: 0px; padding: 0px">Regards,</p>
    <p style="margin: 0px; padding: 0px">Homeplate Team</p>
    <img src="{{ env('filePath') . 'storage/admin/new_logos/main-logo-mail.png' }}" class="object-fit-cover img-fluid"
        width="80" alt="">
    <p style="margin: 0px; padding: 0px">support@homeplate.ca</p>
</body>
</html>
