<!DOCTYPE html>
<html>

<head>
    <title>Order Delivery Notification</title>
</head>

<body>
    <p><strong>Dear {{ ucfirst($driver->firstName).' '.ucfirst($driver->lastName) }},</strong></p>
    <p>We have a new order for you to consider.</p>
    <p>Order Details:</p>
    <ul>
        <li>Order ID: {{ $suborder->order_id }}</li>
        <li>Delivery Date: {{ $suborder->orders->delivery_date }}</li>
        <li>Chef's Name: {{ ucfirst($suborder->chefs->firstName) . ' ' . ucfirst($suborder->chefs->lastName) }}</li>
        <li>Chef's Kitchen Name: {{ $suborder->chefs->kitchen_name }}</li>
        <!-- Add other relevant suborder details here -->
    </ul>
    <p>Please log in to your account for more details and to accept the order.</p>
    <p>Thank you,</p>

    {{-- <p>Dear {{ ucfirst($driver->firstName).' '.ucfirst($driver->lastName) }},</p>
    <p>You have a new delivery scheduled for {{ $suborder->orders->delivery_date }}.</p>
    <p>Please check your app for more details.</p>
    <br> --}}
    <br>
    <p style="margin: 0px; padding: 0px">Regards,</p>
    <p style="margin: 0px; padding: 0px">Homeplate Team</p>
    <img src="{{ env('filePath') . 'storage/admin/new_logos/main-logo-mail.png' }}" class="object-fit-cover img-fluid"
        width="80" alt="">
    <p style="margin: 0px; padding: 0px">support@homeplate.ca</p>
</body>

</html>
