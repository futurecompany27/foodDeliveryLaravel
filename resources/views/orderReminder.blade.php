<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,
    initial-scale=1.0">
    <title>{{$subject}}</title>
    <link rel=" stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <p>Hello {{$chefName}},</p>
    @if($slot == 1)
    <p>This is your first reminder to accept the order. Continue to earn and grow with us.</p>
    @elseif($slot == 2)
    <p>Hurry up! You are missing out on a good opportunity. This is your second last chance to accept the order.</p>
    <p>eeated issed oders will leadtyou account being blocked in the future.</p>
    @elseif($slot == 3)
    <p>Your order eeds approval and ou have only {{$timeElapsedInMinutes}} to accept this order. Ac Fast before it is
        cancelld.</p>
    <p>your order will be cancelled auomatically if not accepted.</p>
    @elseif($slot == 4)
    <p>Here's quick reminder! This is final chance to accept the order.</p>
    @endif
    <p>Click on the link to know about this order <a href="javascript:void(0)">Click here</a></p>

    <br>
    <br>
    <br>

    <p style="margin: 0px; padding: 0px">Regards,</p>
    <p style="margin: 0px; padding: 0px">Homeplate Team</p>
    <img src="{{ env('filePath') . 'storage/admin/new_logos/main-logo-mail.png' }}" class="object-fit-cover img-fluid"
        width="80" alt="">
    <p style="margin: 0px; padding: 0px">support@homeplate.ca</p>

</body>

</html>
