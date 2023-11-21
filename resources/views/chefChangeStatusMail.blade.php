<!DOCTYPE html>
<html>
<head>
 <title>Home Shef Registration </title>
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
 
 
 <b>Hi {{ucfirst($firstName)}} {{ucfirst($lastName)}}</b>
 @if($status === 0)
 <p>Your account on the Homeshef is now deactivated.</p>
 <p>For further enquiry kindy mail to support@homehef.com</p> 
@elseif($status === 1)

 <p>Now you <b>Activate</b> on Homeshef</p>
 <p>Welcome to Homeshef. We're thrilled to see you here!
    We're confident that our Food Ordering System will help you to grow your business.</p>
 <p>Now you can add your kitchen food.</p>
 <p>For further enquiry kindy mail to support@homehef.com</p> 
 @elseif($status === 2)
 <p>Homeshef send your account detail to Inreview</p>
 <p>Welcome to Homeshef. We're thrilled to see you here!
    We're confident that our Food Ordering System will help you to grow your business.</p>  
 <p>For further enquiry kindy mail to support@homehef.com</p> 

 @endif
 
 <p>Regards,</p>
 <p>Homeshef Team</p>
</body>
</html> 