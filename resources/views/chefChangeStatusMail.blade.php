<!DOCTYPE html>
<html>

<head>
    <title>Homeplate Chef Registration </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>


    <b>Hi {{ ucfirst($firstName) }} {{ ucfirst($lastName) }}</b>
    @if ($status === 0)
        <p>Your account on the Homeplate is now deactivated.</p>
        <p>For further enquiry kindy mail to support@homehef.com</p>
    @elseif($status === 1)
        <p>Now you <b>Activate</b> on Homeplate</p>
        <p>Welcome to Homeplate. We're thrilled to see you here!
            We're confident that our Food Ordering System will help you to grow your business.</p>
        <p>Now you can add your kitchen food.</p>
        <p>For further enquiry kindy mail to support@homehef.com</p>
    @elseif($status === 2)
        <p>Homeplate send your account detail to Inreview</p>
        <p>Welcome to Homeplate. We're thrilled to see you here!
            We're confident that our Food Ordering System will help you to grow your business.</p>
        <p>For further enquiry kindy mail to support@homehef.com</p>
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
