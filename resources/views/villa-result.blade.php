<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" href="{{asset("app-assets/images/logo/mazaya-logo-dark.png")}}">
    <link rel="shortcut icon" type="image/x-icon" href="{{asset("app-assets/images/logo/mazaya-logo-dark.png")}}">
    <title>نتيجة تقدير التكلفة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            font-family: 'Cairo', sans-serif;
        }
        .logo {
            max-height: 86px !important;
            margin-bottom: 25px;
            width: 100px !important;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            font-size: 1.25rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="text-center">
            <img src="{{asset('app-assets/images/logo/mazaya-logo-dark.png')}}" alt="Logo" class="logo"> <strong style="font-size: x-large;">Mazaya</strong> 
            <h1>نتيجة تقدير التكلفة الخاصة بالمنزل او الفيلا</h1>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <!-- Dynamic Summary -->
        <p class="text-center">
            عرض سعر لفيلا تتكون من 
            @foreach ($selectedRooms as $room)
                {{ $room['quantity'] }} {{ $room['name'] }}
                @if (!$loop->last) و @endif
            @endforeach
            بإجمالي عدد {{ $totalPartitions }} تقسيمات.
        </p>

        <!-- Room Details -->
        @foreach ($selectedRooms as $room)
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                {{ $room['name'] }} (عدد: {{ $room['quantity'] }})
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>اسم الجهاز</th>
                            <th>الكمية</th>
                            <th>سعر الوحدة</th>
                            <th>الإجمالي (غرفة واحدة)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($room['devices'] as $device)
                        <tr>
                            <td>{{ $device['name'] }}</td>
                            <td>{{ $device['quantity'] }}</td>
                            <td>{{ $device['unit_price'] }} ريال</td>
                            <td>{{ $device['total_price'] / $room['quantity'] }} ريال</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <h5 class="text-end">
                    إجمالي تكلفة {{ $room['name'] }}  للواحدة: 
                    {{ $room['total_cost'] / $room['quantity'] }} ريال
                </h5>
                <h5 class="text-end">
                    إجمالي تكلفة {{ $room['name'] }} لجميع الغرف: 
                    {{ $room['total_cost'] }} ريال
                </h5>
            </div>
        </div>
        @endforeach


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
        <div class="card mb-4">
            <div class="card-header bg-secondary text-dark">ملحوظة:</div>
            <div class="card-body">
                 : هذه تكلفه الاجهزه فقط لا تشمل تكلفه اي اسلاك او امدادت كهربيه او امدادت خاصه بالانترنت 
            </div>
        </div>
    </div>
</body>
</html>
