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
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="text-center">
            <img src="your-logo.png" alt="Logo" class="logo">
            <h1>تقدير تكلفة الفلة</h1>
        </div>

        <!-- Form to Enter Number of Rooms -->
        <form id="villaForm" action="{{ route('user.villa-pricing.calculate') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="number_of_rooms" class="form-label">عدد الغرف</label>
                <input type="number" id="number_of_rooms" name="number_of_rooms" class="form-control" required min="1" oninput="generateRoomFields()">
            </div>

            <!-- Room Type Fields -->
            <div id="roomFields"></div>

            <button type="submit" class="btn btn-primary w-100 mt-4">حساب التكلفة</button>
        </form>
    </div>

    <script>
        const rooms = @json($rooms);

        function generateRoomFields() {
            const numberOfRooms = document.getElementById('number_of_rooms').value;
            const roomFields = document.getElementById('roomFields');
            roomFields.innerHTML = ''; // Clear previous fields

            for (let i = 0; i < numberOfRooms; i++) {
                const select = document.createElement('select');
                select.name = `room_types[${i}]`;
                select.classList.add('form-select', 'mb-3');
                select.required = true;

                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'اختر نوع الغرفة';
                defaultOption.disabled = true;
                defaultOption.selected = true;
                select.appendChild(defaultOption);

                rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.id;
                    option.textContent = room.name;
                    select.appendChild(option);
                });

                roomFields.appendChild(select);
            }
        }
    </script>
</body>
</html>
