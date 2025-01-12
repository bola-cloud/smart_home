<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" href="{{asset("app-assets/images/logo/mazaya-logo-dark.png")}}">
    <link rel="shortcut icon" type="image/x-icon" href="{{asset("app-assets/images/logo/mazaya-logo-dark.png")}}">
    <title>تقدير تكلفة </title>
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
            <img src="{{asset('app-assets/images/logo/mazaya-logo-dark.png')}}" alt="Logo" class="logo">
            <h1>تقدير تكلفة الي المنزل او الفيلا</h1>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

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
                            <button type="button" class="btn btn-danger remove-row" disabled>حذف</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="text-center">
                <button type="button" class="btn btn-success add-row">إضافة</button>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-4">حساب التكلفة</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            // Initialize Select2
            $('.room-select').select2({ width: '100%' });

            // Add a new row
            $(document).on('click', '.add-row', function () {
                const newRow = `
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
                            <button type="button" class="btn btn-danger remove-row">حذف</button>
                        </td>
                    </tr>`;
                $('#roomTable').append(newRow);
                $('.room-select').select2({ width: '100%' });
                updateDeleteButtons();
            });

            // Remove a row
            $(document).on('click', '.remove-row', function () {
                if ($('#roomTable tr').length > 1) {
                    $(this).closest('tr').remove();
                    updateDeleteButtons();
                }
            });

            // Ensure at least one delete button is disabled if there is only one row
            function updateDeleteButtons() {
                const rows = $('#roomTable tr');
                rows.find('.remove-row').prop('disabled', rows.length <= 1);
            }

            // Initial setup for delete buttons
            updateDeleteButtons();
        });
    </script>
</body>
</html>
