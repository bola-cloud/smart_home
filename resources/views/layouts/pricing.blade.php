<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
        }
        .sidebar {
            height: 100vh;
            background: #343a40;
            color: white;
            position: fixed;
            width: 250px;
            top: 0;
            left: 0;
            padding: 20px 10px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            margin: 15px 0;
            padding: 10px 15px;
            border-radius: 5px;
        }
        .sidebar a:hover {
            background: #495057;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .navbar {
            margin-left: 250px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h4 class="text-center">Admin Panel</h4>
        <hr class="bg-light">
        <a href="{{ route('admin.rooms.index') }}">Manage Rooms</a>
        <a href="#">Manage Pricing</a>
        <a href="#">Reports</a>
        <a href="#">Settings</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <nav class="navbar navbar-light bg-light shadow-sm mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">Pricing Administration</a>
            </div>
        </nav>

        <div class="container">
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
