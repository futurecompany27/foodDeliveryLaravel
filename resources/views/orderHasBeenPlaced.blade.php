<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,
    initial-scale=1.0">
    <title>{{ $subject }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    {{-- <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
        }
    </style> --}}
</head>

<body>
    <p style="margin: 0px; padding: 0px"><strong>Dear {{ $userName }},</strong></p>
    <br>
    <p style="margin: 0px; padding: 0px;">We are thrilled to inform you that your order has been successfully processed
        and waiting for chef’s
        confirmation! Thank you for choosing Homeplate for your Food needs. We're excited to serve you and ensure a
        seamless experience throughout your journey with us.
    </p>
    <br>
    <br>
    <p style="margin: 0px; padding: 0px; color: rgb(13, 117, 253)"><strong>Here are the details of your order:</strong>
    </p>
    <br>

    {{-- <table>
        <tr>
            <th>Order Number: </th>
            <td>{{ $order_id }}</td>
        </tr>
        <tr>
            <th>Date of Purchase: </th>
            <td>{{ $created_at }}</td>
        </tr>
        <tr>
            <th>Items Ordered: </th>
            <td>{{ $total_order_item }}</td>
        </tr>
        <tr>
            <th>Total Amount: </th>
            <td>{{ $grand_total }}</td>
        </tr>
    </table> --}}

    <p style="margin: 0px; padding: 0px"><strong>Order Number: {{ $order_id }} </strong></p>
    <p style="margin: 0px; padding: 0px"><strong>Date of Order: {{ $created_at }} </strong></p>
    <p style="margin: 0px; padding: 0px"><strong> Items Ordered: {{ implode(', ', $dishNames) }}</strong></p>
    <p style="margin: 0px; padding: 0px"><strong>Total Amount: CA$ {{ number_format($grand_total, 2) }} </strong></p>
    {{-- <p style="margin: 0px; padding: 0px"><strong>Items Ordered: @foreach ($dishNames as $dish)
                {{ $dish }}
            @endforeach
        </strong>
    </p> --}}
    {{-- order-item in number only --}}
    {{-- <p style="margin: 0px; padding: 0px"><strong>Items Ordered: {{ $total_order_item }} </strong></p> --}}
    <br>
    <p style="margin: 0px; padding: 0px">Your satisfaction is our top priority, and we're committed to delivering the
        highest quality
        products/services along with exceptional customer service. If you have any questions or require further
        assistance regarding your order, please don't hesitate to contact us. Our dedicated support team is here to help
        you every step of the way.
    </p>

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
