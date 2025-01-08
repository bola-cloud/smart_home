<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cost Estimation Tool</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
        background-color: #f8f9fa;
    }
    .card-header {
        font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="container my-4">
    <h1 class="text-center">Smart Home Cost Estimation</h1>
    
    <!-- Input Form -->
    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        Add Item
      </div>
      <div class="card-body">
        <form id="addItemForm">
          <div class="row">
            <div class="col-md-4">
              <label for="area" class="form-label">Room/Area</label>
              <input type="text" id="area" class="form-control" placeholder="E.g., Living Room" required>
            </div>
            <div class="col-md-3">
              <label for="item" class="form-label">Item</label>
              <input type="text" id="item" class="form-control" placeholder="E.g., Mini R2" required>
            </div>
            <div class="col-md-2">
              <label for="quantity" class="form-label">Quantity</label>
              <input type="number" id="quantity" class="form-control" required>
            </div>
            <div class="col-md-2">
              <label for="price" class="form-label">Unit Price</label>
              <input type="number" id="price" class="form-control" required>
            </div>
            <div class="col-md-1 d-flex align-items-end">
              <button type="submit" class="btn btn-primary">Add</button>
            </div>
          </div>
        </form>
      </div>
    </div>
    
    <!-- Cost Table -->
    <div class="card mb-4">
      <div class="card-header bg-success text-white">
        Cost Breakdown
      </div>
      <div class="card-body">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Room/Area</th>
              <th>Item</th>
              <th>Quantity</th>
              <th>Unit Price</th>
              <th>Total</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="costTableBody">
            <!-- Rows will be dynamically inserted -->
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Summary -->
    <div class="card">
      <div class="card-body text-end">
        <h5>Total Cost: <span id="totalCost">0</span> SAR</h5>
        <button class="btn btn-secondary" id="exportBtn">Export as PDF</button>
      </div>
    </div>
  </div>

  <script>
    // JavaScript for dynamic functionality will go here
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
