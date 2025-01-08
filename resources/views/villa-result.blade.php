<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتيجة تقدير التكلفة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            font-family: 'Cairo', sans-serif;
        }
        .logo {
            max-height: 60px;
            margin-bottom: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            font-size: 1.25rem;
            font-weight: bold;
        }
        .card-header.bg-primary {
            background-color: #007bff !important;
            color: white;
        }
        .card-header.bg-success {
            background-color: #28a745 !important;
            color: white;
        }
        .card-header.bg-warning {
            background-color: #ffc107 !important;
            color: #212529;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="text-center">
            <img src="your-logo.png" alt="Logo" class="logo">
            <h1>نتيجة تقدير التكلفة</h1>
        </div>

        <!-- Room Details -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">تفاصيل الغرف</div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>اسم الغرفة</th>
                            <th>الأجهزة</th>
                            <th>التكلفة الإجمالية</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($selectedRooms as $room)
                        <tr>
                            <td>{{ $room['name'] }}</td>
                            <td>
                                <ul>
                                    @foreach ($room['devices'] as $device)
                                    <li>
                                        {{ $device['name'] }}: الكمية {{ $device['quantity'] }},
                                        سعر الوحدة {{ $device['unit_price'] }} ريال,
                                        الإجمالي {{ $device['total_price'] }} ريال
                                    </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>{{ $room['total_cost'] }} ريال</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Internet Points -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">نقاط الإنترنت</div>
            <div class="card-body">
                <p>عدد نقاط الإنترنت المطلوبة: {{ $accessPoints }}</p>
                <p>التكلفة الإجمالية لنقاط الإنترنت: {{ $accessPointsCost }} ريال</p>
            </div>
        </div>

        <!-- Total Cost -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">إجمالي التكلفة</div>
            <div class="card-body">
                <h4>{{ $totalCost }} ريال</h4>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
