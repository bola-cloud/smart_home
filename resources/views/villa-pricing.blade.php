<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقدير تكلفة الفلة</title>
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
        .room-table {
            border-radius: 15px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
        }
        .room-header {
            color: white;
            font-weight: bold;
        }
        .room-header-1 {
            background-color: #007bff;
        }
        .room-header-2 {
            background-color: #28a745;
        }
        .room-header-3 {
            background-color: #ffc107;
        }
        .room-header-4 {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="text-center">
            <img src="your-logo.png" alt="Logo" class="logo">
            <h1>تقدير تكلفة الفلة</h1>
        </div>
        
        <form action="{{ route('user.villa-pricing.calculate') }}" method="POST">
            @csrf

            @foreach ($rooms as $index => $room)
            <div class="card room-table mb-4">
                <div class="card-header room-header room-header-{{ ($index % 4) + 1 }}">
                    {{ $room->name }}
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="room_{{ $room->id }}" class="form-label">عدد الغرف</label>
                        <input type="number" name="room_quantities[{{ $room->id }}]" id="room_{{ $room->id }}" class="form-control" value="0" min="0">
                    </div>
                    @if ($room->devices->count() > 0)
                    <h5>الأجهزة المتوفرة:</h5>
                    <ul>
                        @foreach ($room->devices as $device)
                        <li>{{ $device->name }} - الكمية: {{ $device->quantity }} - سعر الوحدة: {{ $device->unit_price }} ريال</li>
                        @endforeach
                    </ul>
                    @else
                    <p class="text-muted">لا توجد أجهزة مضافة لهذا النوع من الغرف.</p>
                    @endif
                </div>
            </div>
            @endforeach

            <button type="submit" class="btn btn-primary w-100">حساب التكلفة</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
