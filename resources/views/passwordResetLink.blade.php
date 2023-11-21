<!DOCTYPE html>
<html>
<head>
 <title>HomeShef Registration </title>
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
 
 <b>Hi {{ucfirst($firstName)}} {{ucfirst($lastName)}}</b>

 <p>Kindly reset your {{ucfirst($user_type)}}'s password by clicking on the link below</p>

 <a class="btn btn-primary" href="{{ env('domain') . 'reset-password?id=' . $id . '&user_type=' . $user_type . '&token=' . $token }}">Reset password<a>

 <p>Regards,</p>
 <p>Homeshef Team</p>
</body>
</html> 