<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous"> --}}
    <title>order-invoice</title>

    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 10px;
            color: #2c2a2a;
        }

        h1,
        h2,
        h3,
        p {
            margin: 0 0 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #e4e3e3;
        }

        tfoot td {
            font-weight: bold;
        }

        tfoot {
            background-color: #f9f9f9;
        }


        th {
            text-align: center;
        }

        .subtotal,
        .pst,
        .gst,
        .shipping,
        .total,
        .paid {
            text-align: right;
            padding-right: 20px;
        }

        tr,
        th,
        td {
            outline: 1px solid rgba(29, 28, 28, 0.829);
        }

        hr {
            margin: 3px;
            padding: 0px;
        }

        .footer {
            text-align: left;
            bottom: 0;
            width: 100%;
            font-size: 10px;
            color: #494949;
            padding-top: 10px;
        }

        .text-center {
            text-align: center
        }
    </style>
</head>

<body>

    <div class="row">
        <div class="col-6">
            <div class="text-start">
                <img src="{{ public_path('/storage/admin/new_logos/main-logo-mail.png') }}" width="100"
                    alt="">
            </div>
            <div style="margin-top:5px"> Date: {{ \Carbon\Carbon::now()->format('d-M-Y') }}</div>
        </div>
        <div class="col-6">
            <div style="margin-top:5px">
                <h3 class="text-dark" style="font-size: 18px"><strong>INVOICE</strong></h3>
            </div>
        </div>
        <hr>
        <hr>
    </div>

    <div>

    </div>

    <div>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Sub Order ID</th>
                    <th>Delivery Date</th>
                    <th>Delivery Time</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center">{!! $data->order_id !!}</td>

                    <td style="text-align: center">{!! $data->sub_order_id !!}</td>

                    <td style="text-align: center">{!! $data->orders->delivery_date !!}</td>

                    <td style="text-align: center">{!! $data->orders->delivery_time !!}</td>
                </tr>
            </tbody>
        </table>

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
                @foreach ($data->orderItems as $item)
                    <tr>
                        <td style="text-align: center">
                            {!! Str::ucfirst($item->foodItem->dish_name) !!}

                        </td>
                        <td style="text-align: center">
                            <img src="{{ asset($item->foodItem->dishImageThumbnail) }}" width="60" height="60"
                                alt="">
                        </td>
                        <td style="text-align: center">
                            {!! $item->quantity !!}
                        </td>
                        <td style="text-align: center">
                            {!! $item->price !!}
                        </td>
                        <td style="text-align: center">
                            {!! $item->total !!}
                        </td>
                    <tr>
                @endforeach
                <tr>
                    <td></td>
                    <td></td>
                    <td colspan="2">Total</td>
                    <td>{!! $data->amount !!} CA$</td>

                </tr>
                <!-- @php
                    $totalTax = 0;
                @endphp

                @php
                    $taxDetails = is_string($data->sub_order_tax_detail)
                        ? json_decode($data->sub_order_tax_detail, true)
                        : $data->sub_order_tax_detail;
                    $totalTax = 0;
                @endphp
                @if ($data->chefs->is_tax_document_completed = 0)
                    <tr></tr>
                @else
                    @foreach ($taxDetails as $tax)
                        <tr>
                            <td></td>
                            <td></td>
                            @foreach ($tax as $key => $value)
                                @if ($key != 'Amount')
                                    <td colspan="2">{{ $key }} ({{ $value }}%)</td>
                                @else
                                    <td>{!! number_format($value, 2) !!} CA$</td>
                                    {{ $totalTax = +$value }}
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                @endif -->


                <!-- <tr>
                    <td></td>
                    <td></td>
                    <td colspan="2"><strong> Total + Tax</strong></td>
                    <td><strong> {{ number_format($data->amount + $totalTax, 2) }} CA$ </strong></td>

                </tr> -->

                {{-- EXPENSES PART --}}
                <tr>
                    <td style="background-color: #666;" colspan="5"></td>

                </tr>
                <tr>
                    <td style="text-align: start;" colspan="5">Expenses</td>

                </tr>
                @php
                    $commissionTaxes = is_string($data->chef_commission_taxes)
                        ? json_decode($data->chef_commission_taxes, true)
                        : $data->chef_commission_taxes;
                    $chefTotalTax = 0;
                @endphp
                <tr>
                    <td></td>
                    <td></td>
                    <td colspan="2">Commission ({{ $data->chef_commission }}%)</td>
                    <td>{{ $data->chef_commission_amount }} CA$</td>
                </tr>

                @foreach ($commissionTaxes as $tax)
                    <tr>
                        <td></td>
                        <td></td>
                        @foreach ($tax as $key => $value)
                            @if ($key != 'Amount')
                                <td colspan="2">{{ $key }} on commission ({{ number_format($value, 3) }}%)</td>
                            @else
                                <td>{!! number_format($value, 2) !!} CA$</td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach



                {{-- PROMOTION --}}

                <tr>
                    <td style="background-color: #666;" colspan="5"></td>

                </tr>
                <tr>
                    <td style="text-align: start;" colspan="5">Promotion</td>

                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td colspan="2">Tip Amount</td>
                    <td>
                        @if ($data['tip_amount'] == 0)
                            0.00 CA$
                        @else
                            {{ $data['tip_amount'] }} CA$
                        @endif
                    </td>

                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td colspan="2">Promotion</td>
                    <td> - </td>
                </tr>

                <tr>
                    <td style="width: 50px; background-color:#666" colspan="5"></td>
                </tr>
                <tr>
                    @php
                        $taxOnCommissionAmount = 0;
                        if (is_array($commissionTaxes)) {
                            foreach ($commissionTaxes as $tax) {
                                foreach ($tax as $key => $value) {
                                    if ($key == 'Amount') {
                                        $taxOnCommissionAmount += $value;
                                    }
                                }
                            }
                        }
                        $finalTotal = $data->amount + $data['tip_amount'] - $data->chef_commission_amount - $taxOnCommissionAmount;
                    @endphp
                    <td style="text-align: left;" colspan="2"><strong>Total Earning</strong></td>
                    <td></td>
                    <td></td>
                    <td> <strong>{{ number_format($finalTotal, 2) }} CA$</strong></td>
                </tr>


            </tbody>
        </table>




        <div class="footer">
            <!-- <p><strong>*** Total Earnings = (Total + Tax) + Tips + Promotion – (Expenses / Commission & Tax On Commission) ***</strong></p> -->
            <p><strong>*** Total Earnings = Total + Tip Amount + Promotion – (Expenses / Commission & Tax On Commission) ***</strong></p>
        </div>



    </div>



</body>

{{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
</script> --}}

</html>
