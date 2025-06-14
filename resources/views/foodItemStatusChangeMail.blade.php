<!DOCTYPE html>
<html>

<head>
    <title>Homeplate Registration </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>


    <b>Hello {{ ucwords($firstName) }} {{ ucfirst($lastName) }},</b>
    <p>
        @if($approved_status == 'approved')
            Congratulations! Your dish - <b>{{ $food_name }}</b> has been approved by our Admin. You can check the same on the website. You can edit the details anytime you want but will have to wait till the admin approves it again. It won't take us much time though.
        @elseif($approved_status == 'unapproved')
            We regret to inform you that your dish - <b>{{ $food_name }}</b> has been rejected by our Admin. Please review and update the details accordingly And Please connect with us on <a href="mailto:support@homeplate.ca">support@homeplate.ca</a> for any further assistance.
        @else
            Your dish - <b>{{ $food_name }}</b> is currently pending approval. We will notify you once it is reviewed.
        @endif
    </p>

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
