<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقدير تكلفة الفلة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
            <h1>تقدير تكلفة الفيلا</h1>
        </div>

        <!-- Form to Add Rooms -->
        <form id="villaForm" action="{{ route('user.villa-pricing.calculate') }}" method="POST">
            @csrf
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>نوع الغرفة</th>
                        <th>عدد الغرف</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody id="roomTable">
                    <tr>
                        <td>
                            <select name="room_types[]" class="form-select room-select" required>
                                <option value="" selected disabled>اختر نوع الغرفة</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" name="room_quantities[]" class="form-control" required min="1">
                        </td>
                        <td>
                            <button type="button" class="btn btn-success add-row">إضافة</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary w-100 mt-4">حساب التكلفة</button>
        </form>
    </div>

    <script>
        // JavaScript to dynamically add a new row
        document.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('add-row')) {
                // Prevent default action
                e.preventDefault();

                // Get the roomTable element
                const roomTable = document.getElementById('roomTable');

                // Create a new row
                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td>
                        <select name="room_types[]" class="form-select room-select" required>
                            <option value="" selected disabled>اختر نوع الغرفة</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" name="room_quantities[]" class="form-control" required min="1">
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger remove-row">حذف</button>
                    </td>
                `;

                // Append the new row to the table
                roomTable.appendChild(newRow);
            }

            // Remove a row if the delete button is clicked
            if (e.target && e.target.classList.contains('remove-row')) {
                e.preventDefault();
                e.target.closest('tr').remove();
            }
        });
    </script>
</body>
</html>
