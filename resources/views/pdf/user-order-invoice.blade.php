<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>customer-order-invoice</title>

    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 10px;
            color: #2c2a2a;
        }

        hr {
            margin: 3px;
            padding: 0px;
        }

        p {
            padding: 5px, 0px;
            margin: 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #e4e3e3;
        }

        tr,
        th,
        td {
            outline: 1px solid rgba(29, 28, 28, 0.829);
        }

        table {
            width: 100%;
        }
        .footer {
            text-align: left;
            bottom: 0;
            width: 100%;
            font-size: 10px;
            color: #494949;
            padding-top: 10px;
        }
    </style>

</head>

<body>

    <div>
        <div>
            <img src="{{ public_path('/storage/admin/new_logos/main-logo-mail.png') }}" width="120" alt="">
            <h4 style="margin: 3px, 3px">
                Date: {{ \Carbon\Carbon::now()->format('d-M-Y') }}
            </h4>
        </div>
        <h3 style="margin: 3px, 3px;"><strong>#INVOICE</strong></h3>
        <br>
        <hr>
        <hr>
        <br>

        <div class="order-details">
            <p><strong>Order ID:- </strong> {!! $data->order_id !!}</p>
            <p><strong>Customer Name:- </strong> {!! $data->username !!}</p>
            <p><strong>Shipping Address:- </strong> {!! $data->shipping_address !!}</p>
            <p><strong>Order Date:- </strong> {!! $data->created_at->format('d/m/Y / h:i:s A') !!} </p>
            <p><strong>Schedule Date:- </strong> {!! $data->delivery_date !!} / {!! $data->delivery_time !!} </p>
            <p><strong>Payment Mode:- </strong> {!! $data->payment_mode !!} </p>
        </div>

        <div style="padding-top:15px">
            <table>
                <thead>
                    <tr>
                        <th>Dish Name</th>
                        <th>Dish Image</th>
                        <th>Quantity</th>
                        <th>Price (in CA$)</th>
                        <th>Sub-Total (in CA$)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data->subOrders as $subOrder)
                        {{-- Loop through sub_orders --}}
                        @foreach ($subOrder->OrderItems as $order)
                            {{-- Loop through order_items --}}
                            <tr>
                                <td style="text-align: center">{!! Str::ucfirst($order->FoodItem->dish_name) !!}</td>
                                <td style="text-align: center"><img
                                        src="{{ asset($order->FoodItem->dishImageThumbnail) }}" width="60"
                                        height="60" alt=""></td>
                                <td style="text-align: center">{{ $order->quantity }}</td>
                                <td style="text-align: center">{{ $order->price }}</td>
                                <td style="text-align: center">{{ $order->total }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
                {{-- <thead>
                    <tr>
                        <th colspan="5">Price Details</th>
                    </tr>
                </thead> --}}
                <tbody>
                    <tr>
                        <td></td>
                        <td></td>
                        <td colspan="2"><strong>Total</strong></td>
                        <td>{{ number_format($data->order_total, 2) }} CA$</td>
                    </tr>
                    @php
                        $totalTax = 0;
                    @endphp
                    @foreach ($data['tax_types'] as $tax)
                        <tr>
                            <td></td>
                            <td></td>
                            @foreach ($tax as $key => $value)
                                @if ($key == 'GST' || $key == 'QST')
                                    <td colspan="2">{{ $key }} ({{ $value }}%)</td>
                                @else
                                    <td>
                                        {{ number_format($value, 2) }} CA$
                                    </td>
                                    @php
                                        $totalTax += $value;
                                    @endphp
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                    <tr>
                        <td></td>
                        <td></td>
                        <td colspan="2"><strong> Total (Incl. Tax)</strong></td>
                        <td><strong> {{ number_format($data->grand_total, 2) }} CA$ </strong></td>
                        {{-- <td><strong> {{ number_format($data->order_total + $totalTax, 2) }} CA$ </strong></td> --}}

                    </tr>
                </tbody>

                <tr>
                    <th colspan="5">Delivery Shipping</th>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td colspan="2">GST on Delivery (5%)</td>
                    <td>0 CA$</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td colspan="2">QST on Delivery (9.975%)</td>
                    <td>0 CA$</td>
                </tr>
                {{-- <tr>
                    <th colspan="5">Total Invoice</th>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td colspan="2">GST on Sale (5%)</td>
                    <td>0 CA$</td>
                </tr> --}}

                <tr>
                    <th colspan="5">Tips</th>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td colspan="2">Tip Amount</td>
                    @foreach ($data->subOrders as $subOrder)
                        <td>{{ number_format($subOrder->tip_amount, 2) }} CA$</td>
                        @php
                            $tipAmount = $subOrder->tip_amount;
                        @endphp
                    @endforeach
                </tr>
                <tr>
                    @php
                        $grandTotal = $data->grand_total + $tipAmount;
                    @endphp
                    {{-- <td></td>
                    <td></td>
                    <td colspan="2"><strong>Grand Total</strong></td>
                    <td>{{ number_format($grandTotal,2)}} CA$</td> --}}

                    <td style="text-align: center; background-color:#e4e3e3" colspan="3"><strong>Grand Total</strong>
                    </td>

                    <td style="text-align: center" colspan="2"> <strong>{{ number_format($grandTotal, 2) }}
                            CA$</strong></td>
                </tr>
            </table>
        </div>
        <div class="footer">
            <p><strong>*** Grand Total = (Total + Tax) + Tips + (Delivery Tax)***</strong></p>
        </div>
    </div>


</body>


</html>
