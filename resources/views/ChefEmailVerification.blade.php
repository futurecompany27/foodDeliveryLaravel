<!DOCTYPE html> <html>

<head> <title>Home Shef Registration </title> <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    </head>

    <body>

        <b>Hi {{ucfirst($firstName)}} {{ucfirst($lastName)}}</b>
        <p>Your account has been created successfully.</p>
        <p>You may access your account only after the verification of your documents and details.</p>

        <p>Kindly verify your email by clicking on the link below</p>
        <a class="btn btn-primary" href="{{ env('domain'). 'verified-mail?id=' . $id . '&type=chef' }}">Email
            Verification</a>
        <p>Regards,</p>
        
    </body>

    </html>