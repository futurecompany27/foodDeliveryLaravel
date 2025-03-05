<!DOCTYPE html>
<html>

<head>
    <title>Label Data</title>
    {{-- <style>
        /* Define CSS styles for proper formatting */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1, h2 {
            color: #007bff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-primary {
            margin-bottom: 20px;
        }
    </style> --}}
    <style>
        * {
            margin: 0px;
            padding: 0px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 10px;
            padding: 10px;
            /* color: #333;
            background-color: #f4f4f4; */
        }

        /* body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
            color: #333;
        } */

        /* .container {
            max-width: 800px;
            margin: 10px auto;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        } */

        h2 {
            text-align: center;
            color: #3108c2;
            font-size: 20px;
        }

        h3 {
            padding-top: 15px;
            padding-bottom: 10px;
            color: #3108c2;
            font-size: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #dfdcdc;
        }

        th {
            background-color: #eeeeee;
            color: #333;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        tr:hover {
            background-color: #f2f2f2;
        }

        .text-primary {
            color: #007bff;
            margin-bottom: 20px;
        }

        th:first-child,
        td:first-child {
            width: 40%;
            font-size: 12px;
        }

        th:last-child,
        td:last-child {
            width: 60%;
            font-size: 12px;
        }

        /* td{
            font-size: 12px;
        }
        th{
            font-size: 12px;
        } */

        @media screen and (max-width: 768px) {
            .container {
                margin: 10px;
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="container">

        <div><img src="{{ public_path('/storage/admin/new_logos/main-logo-mail.png) }}" width="80" alt=""></div>
        <h2 style="padding-bottom: 15px; padding-top:10px">Restaurant & Retail Certificate</h2>

        <h3>Business Details</h3>
        <table>
            {{-- <tr>
                <th>Status</th>
                <td>{{ $data->status }}</td>
            </tr> --}}
            <tr>
                <th>Business Name</th>
                <td>{{ $data->business_name }}</td>
            </tr>
            <tr>
                <th>Business Phone Number</th>
                <td>{{ $data->business_mobile }}</td>
            </tr>
            <tr>
                <th>Civic Number</th>
                <td>{{ $data->civic_number }}</td>
            </tr>
            <tr>
                <th>Street Name</th>
                <td>{{ $data->street_name }}</td>
            </tr>
            <tr>
                <th>City</th>
                <td>{{ $data->city }}</td>
            </tr>
            <tr>
                <th>Postal-Code</th>
                <td>{{ $data->postal_code }}</td>
            </tr>
            <tr>
                <th>License Plate Number</th>
                <td>{{ $data->vehicle_number }}</td>
            </tr>
            <tr>
                <th>Start Date Of Activities</th>
                <td>{{ $data->start_date }}</td>
            </tr>
        </table>

        <h3>Applicant Information</h3>
        <table>
            <tr>
                <th>Your/Owner Name</th>
                <td>{{ $data->owner_name }}</td>
            </tr>
            <tr>
                <th>Company Name</th>
                <td>{{ $data->company_name }}</td>
            </tr>
            <tr>
                <th>Enterprise Name</th>
                <td>{{ $data->enterprise_number }}</td>
            </tr>
            <tr>
                <th>Company Mobile Number</th>
                <td>{{ $data->company_mobile }}</td>
            </tr>
            <tr>
                <th>Applicant Civic Number</th>
                <td>{{ $data->applicant_civic_number }}</td>
            </tr>
            <tr>
                <th>Applicant Street Name</th>
                <td>{{ $data->applicant_street_name }}</td>
            </tr>
            <tr>
                <th>Applicant City</th>
                <td>{{ $data->applicant_city }}</td>
            </tr>
            <tr>
                <th>Applicant Postal-Code</th>
                <td>{{ $data->applicant_postal_code }}</td>
            </tr>
            <tr>
                <th>Applicant Province</th>
                <td>{{ $data->applicant_province }}</td>
            </tr>
            <tr>
                <th>Applicant Country</th>
                <td>{{ $data->applicant_country }}</td>
            </tr>
        </table>

        <h3>Permit Category Selection</h3>
        <table>
            <tr>
                <th>General Preparation</th>
                <td>{{ $data->catering_general }}</td>
            </tr>
            <tr>
                <th>Maintaining Hot Or Cold</th>
                <td>{{ $data->catering_hot_cold }}</td>
            </tr>
            <tr>
                <th>General Preparation With Buffet</th>
                <td>{{ $data->catering_buffet }}</td>
            </tr>
            <tr>
                <th>Maintaining Hot or Cold With Buffet</th>
                <td>{{ $data->catering_maintaining }}</td>
            </tr>
            <tr>
                <th>General Preparation</th>
                <td>{{ $data->retail_general }}</td>
            </tr>
            <tr>
                <th>Maintaining Hot or Cold</th>
                <td>{{ $data->retail_maintaining }}</td>
            </tr>
        </table>

        <h3 style="margin-top:40px">Permit Cost Calculation</h3>
        <table>
            <tr>
                <th>Annual Base Rate</th>
                <td>{{ $data->annual_rate }}</td>
            </tr>
            <tr>
                <th>Additional Unit</th>
                <td>{{ $data->additional_unit }}</td>
            </tr>
            <tr>
                <th>Total Additional unit</th>
                <td>{{ $data->total_unit }}</td>
            </tr>
            <tr>
                <th>Total Amount</th>
                <td>{{ $data->total_amount }}</td>
            </tr>
        </table>

        <h3>Regulatory Requirement To Obtain A Permit</h3>
        <table>
            <tr>
                <th>Having a facility dedicated to food preparation</th>
                <td>{{ $data->facility_dedicated }}</td>
            </tr>
            <tr>
                <th>Having a sink in the area, premises, or vehicle for food preparation.</th>
                <td>{{ $data->sink_area_premises }}</td>
            </tr>
            <tr>
                <th>Having access to potable running water, both cold and hot (at 60 cl or higher)</th>
                <td>{{ $data->potable_water_access }}</td>
            </tr>
            <tr>
                <th>Having a dispenser for liquid or powdered soap and disposable towels</th>
                <td>{{ $data->regulatory_dispenser }}</td>
            </tr>
            <tr>
                <th>Having a system for the recovery or evacuation o wastewater</th>
                <td>{{ $data->recovery_evacuation }}</td>
            </tr>
            <tr>
                <th>Having aventilation system adapted to operational activities</th>
                <td>{{ $data->ventilation_system }}</td>
            </tr>
            <tr>
                <th>Having a container for waste</th>
                <td>{{ $data->waste_container }}</td>
            </tr>
        </table>

        <h3>Declaration</h3>
        <table>
            <tr>
                <th>Manager Number</th>
                <td>{{ $data->manager_number }}</td>
            </tr>
            <tr>
                <th>Applicant Name</th>
                <td>{{ $data->applicant_name }}</td>
            </tr>
            <tr>
                <th>Date :-</th>
                <td>{{ $data->declaration_date }}</td>
            </tr>
            <tr>
                <th>Message:-</th>
                <td>{{ $data->message }}</td>
            </tr>

        </table>
        {{-- <tr>
            <th>Signature:-</th>
            <td><img src="{{public_path($data->signature)}}" alt="signature" width="200px"></td>
        </tr> --}}
        {{-- <div style="width: 50%; border:2px solid grey; ">
        </div> --}}
        <table>
            <tr>
                <th>Signature:</th>
                <td>
                    <img src="{{ public_path($data->signature) }}" alt="Signature" width="100px">
                </td>
            </tr>
        </table>
    </div>
</body>
{{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
</script> --}}

</html>

<!-- food_license.blade.php -->
