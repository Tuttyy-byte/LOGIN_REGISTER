<?php
session_start();
require_once 'weather_db_connection.php';

// Check if user is logged in from your existing login system
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first to access weather dashboard.");
    exit();
}

// Handle CRUD Operations
// CREATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_weather'])) {
    $location_id = mysqli_real_escape_string($weather_conn, $_POST['location_id']);
    $temperature = mysqli_real_escape_string($weather_conn, $_POST['temperature']);
    $humidity = mysqli_real_escape_string($weather_conn, $_POST['humidity']);
    $wind_speed = mysqli_real_escape_string($weather_conn, $_POST['wind_speed']);
    $precipitation = mysqli_real_escape_string($weather_conn, $_POST['precipitation']);
    $weather_condition = mysqli_real_escape_string($weather_conn, $_POST['weather_condition']);
    $recorded_date = mysqli_real_escape_string($weather_conn, $_POST['recorded_date']);
    $recorded_by = $_SESSION['user_id'];
    
    $sql = "INSERT INTO weather_records (location_id, temperature, humidity, wind_speed, precipitation, weather_condition, recorded_date, recorded_by) 
            VALUES ('$location_id', '$temperature', '$humidity', '$wind_speed', '$precipitation', '$weather_condition', '$recorded_date', '$recorded_by')";
    
    if (mysqli_query($weather_conn, $sql)) {
        $success = "✅ Weather record added successfully!";
        mysqli_query($weather_conn, "INSERT INTO weather_activity_logs (user_id, action, details) VALUES ('{$_SESSION['user_id']}', 'CREATE', 'Added weather record for location ID: $location_id')");
    } else {
        $error = "❌ Error: " . mysqli_error($weather_conn);
    }
}

// UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_weather'])) {
    $id = mysqli_real_escape_string($weather_conn, $_POST['record_id']);
    $temperature = mysqli_real_escape_string($weather_conn, $_POST['temperature']);
    $humidity = mysqli_real_escape_string($weather_conn, $_POST['humidity']);
    $wind_speed = mysqli_real_escape_string($weather_conn, $_POST['wind_speed']);
    $precipitation = mysqli_real_escape_string($weather_conn, $_POST['precipitation']);
    $weather_condition = mysqli_real_escape_string($weather_conn, $_POST['weather_condition']);
    
    $sql = "UPDATE weather_records SET temperature='$temperature', humidity='$humidity', wind_speed='$wind_speed', precipitation='$precipitation', weather_condition='$weather_condition' WHERE id='$id'";
    
    if (mysqli_query($weather_conn, $sql)) {
        $success = "✅ Weather record updated successfully!";
        mysqli_query($weather_conn, "INSERT INTO weather_activity_logs (user_id, action, details) VALUES ('{$_SESSION['user_id']}', 'UPDATE', 'Updated weather record ID: $id')");
    } else {
        $error = "❌ Error: " . mysqli_error($weather_conn);
    }
}

// DELETE
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($weather_conn, $_GET['delete']);
    $sql = "DELETE FROM weather_records WHERE id='$id'";
    if (mysqli_query($weather_conn, $sql)) {
        $success = "✅ Record deleted successfully!";
        mysqli_query($weather_conn, "INSERT INTO weather_activity_logs (user_id, action, details) VALUES ('{$_SESSION['user_id']}', 'DELETE', 'Deleted weather record ID: $id')");
    } else {
        $error = "❌ Error deleting record.";
    }
}

// Fetch data with SQL JOINS for the weather records table
// INNER JOIN - Shows only records that have matching locations
$sql_inner = "SELECT wr.id, l.city_name, l.country, l.region, wr.temperature, wr.humidity, wr.wind_speed, 
              wr.precipitation, wr.weather_condition, wr.recorded_date, u.fullname as recorded_by_name
              FROM weather_records wr
              INNER JOIN locations l ON wr.location_id = l.id
              LEFT JOIN users u ON wr.recorded_by = u.id
              ORDER BY wr.recorded_date DESC";
$weather_data = mysqli_query($weather_conn, $sql_inner);

// LEFT JOIN - Shows all locations even without weather records
$sql_left = "SELECT l.city_name, l.country, l.region, COUNT(wr.id) as record_count, AVG(wr.temperature) as avg_temp, AVG(wr.humidity) as avg_humidity
             FROM locations l
             LEFT JOIN weather_records wr ON l.id = wr.location_id
             GROUP BY l.id";
$left_join_data = mysqli_query($weather_conn, $sql_left);

// Fetch data for charts
$chart_sql = "SELECT l.city_name, AVG(wr.temperature) as avg_temp, AVG(wr.humidity) as avg_humidity, AVG(wr.wind_speed) as avg_wind
              FROM weather_records wr
              INNER JOIN locations l ON wr.location_id = l.id
              GROUP BY l.id";
$chart_data = mysqli_query($weather_conn, $chart_sql);

$cities = [];
$temps = [];
$humidities = [];
$winds = [];

while ($row = mysqli_fetch_assoc($chart_data)) {
    $cities[] = $row['city_name'];
    $temps[] = round($row['avg_temp'], 1);
    $humidities[] = round($row['avg_humidity'], 1);
    $winds[] = round($row['avg_wind'], 1);
}

// Get locations for dropdown
$locations_sql = "SELECT * FROM locations ORDER BY city_name";
$locations = mysqli_query($weather_conn, $locations_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Monitoring Dashboard | IT26 Final Project</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Navigation Bar */
        .nav-bar {
            background: white;
            border-radius: 16px;
            padding: 15px 30px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .nav-bar h1 {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-name {
            font-weight: 600;
            color: #667eea;
        }

        .logout-btn {
            background: #dc2626;
            color: white;
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }

        .back-btn {
            background: #6c757d;
            color: white;
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .chart-card h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 18px;
        }

        /* SQL Join Explanations */
        .join-explanation {
            background: #f0f4ff;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            font-size: 14px;
            border-left: 5px solid #667eea;
        }

        .join-explanation h4 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .join-example {
            background: #e8eefe;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 12px;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            margin: 25px 0;
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .table-container h3 {
            margin-bottom: 15px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background: #f8fafc;
        }

        /* Button Styles */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .edit-btn, .delete-btn, .add-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }

        .edit-btn {
            background: #4CAF50;
            color: white;
        }

        .edit-btn:hover {
            background: #45a049;
        }

        .delete-btn {
            background: #f44336;
            color: white;
        }

        .delete-btn:hover {
            background: #da190b;
        }

        .add-btn {
            background: #2196F3;
            color: white;
            padding: 10px 20px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .add-btn:hover {
            background: #0b7dda;
        }

        /* Search and Sort */
        .search-sort {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-sort input, .search-sort select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .search-sort input {
            flex: 1;
            min-width: 200px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content h3 {
            margin-bottom: 20px;
            color: #667eea;
        }

        .modal-content .input-group {
            margin-bottom: 15px;
        }

        .modal-content label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #334155;
        }

        .modal-content input, .modal-content select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            flex: 1;
        }

        .cancel-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            flex: 1;
        }

        /* Alert Messages */
        .success-message {
            background: #dcfce7;
            color: #16a34a;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .nav-bar {
                flex-direction: column;
                gap: 15px;
            }
            .user-info {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Navigation -->
        <div class="nav-bar">
            <h1>🌤️ Weather Monitoring System</h1>
            <div class="user-info">
                <span class="user-name">👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <span class="user-name">🎭 Role: <?php echo $_SESSION['user_role']; ?></span>
                <a href="dashboard.php" class="back-btn">◀ Back to Main Dashboard</a>
                <a href="logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Charts Section -->
        <div class="dashboard-grid">
            <div class="chart-card">
                <h3>📊 Average Temperature by City</h3>
                <canvas id="tempChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>💧 Average Humidity by City</h3>
                <canvas id="humidityChart"></canvas>
            </div>
        </div>

        <!-- SQL JOIN Explanations Section -->
        <div class="join-explanation">
            <h4>🔗 SQL JOIN Demonstrations Used in This Dashboard</h4>
            <p><strong>INNER JOIN:</strong> Used in main weather records table below. Returns only records that have matching data in both weather_records and locations tables.</p>
            <div class="join-example">
                <code>SELECT * FROM weather_records INNER JOIN locations ON weather_records.location_id = locations.id</code>
            </div>
            
            <p><strong>LEFT JOIN:</strong> Used in the "All Locations Summary" table. Returns ALL locations even if they have no weather records.</p>
            <div class="join-example">
                <code>SELECT * FROM locations LEFT JOIN weather_records ON locations.id = weather_records.location_id</code>
            </div>
            
            <p><strong>RIGHT JOIN:</strong> Similar to LEFT JOIN but starts from the right table (weather_records).</p>
            <div class="join-example">
                <code>SELECT * FROM weather_records RIGHT JOIN locations ON weather_records.location_id = locations.id</code>
            </div>
            
            <p><strong>Purpose:</strong> These joins allow us to display comprehensive weather data by combining information from locations, weather records, and users tables, showing relationships between cities and their weather patterns.</p>
        </div>

        <!-- Add Button -->
        <button class="add-btn" onclick="openAddModal()">➕ Add New Weather Record</button>

        <!-- Search and Sort -->
        <div class="search-sort">
            <input type="text" id="searchInput" placeholder="🔍 Search by city or weather condition..." onkeyup="searchTable()">
            <select id="sortSelect" onchange="sortTable()">
                <option value="date">Sort by Date (Newest)</option>
                <option value="temp_asc">Temperature (Low to High)</option>
                <option value="temp_desc">Temperature (High to Low)</option>
                <option value="city">Sort by City Name</option>
                <option value="humidity">Sort by Humidity</option>
            </select>
        </div>

        <!-- Weather Records Table (INNER JOIN demonstration) -->
        <div class="table-container">
            <h3>📋 Weather Records with Location Details <span style="font-size: 12px; color: #667eea;">(INNER JOIN: weather_records + locations + users)</span></h3>
            <table id="weatherTable">
                <thead>
                    <tr>
                        <th>City</th>
                        <th>Country</th>
                        <th>Region</th>
                        <th>Temp (°C)</th>
                        <th>Humidity (%)</th>
                        <th>Wind (km/h)</th>
                        <th>Precipitation (mm)</th>
                        <th>Condition</th>
                        <th>Recorded Date</th>
                        <th>Recorded By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php mysqli_data_seek($weather_data, 0); ?>
                    <?php while ($row = mysqli_fetch_assoc($weather_data)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['city_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['country']); ?></td>
                        <td><?php echo htmlspecialchars($row['region']); ?></td>
                        <td><?php echo $row['temperature']; ?>°C</td>
                        <td><?php echo $row['humidity']; ?>%</td>
                        <td><?php echo $row['wind_speed']; ?></td>
                        <td><?php echo $row['precipitation']; ?></td>
                        <td><?php echo htmlspecialchars($row['weather_condition']); ?></td>
                        <td><?php echo $row['recorded_date']; ?></td>
                        <td><?php echo htmlspecialchars($row['recorded_by_name'] ?? 'N/A'); ?></td>
                        <td class="action-buttons">
                            <button class="edit-btn" onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo $row['temperature']; ?>', '<?php echo $row['humidity']; ?>', '<?php echo $row['wind_speed']; ?>', '<?php echo $row['precipitation']; ?>', '<?php echo htmlspecialchars($row['weather_condition']); ?>')">Edit</button>
                            <a href="?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('⚠️ Are you sure you want to delete this weather record?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- LEFT JOIN Demonstration Table -->
        <div class="table-container">
            <h3>📊 All Locations Summary <span style="font-size: 12px; color: #667eea;">(LEFT JOIN: locations + weather_records - Shows ALL locations even without records)</span></h3>
            <table>
                <thead>
                    <tr>
                        <th>City</th>
                        <th>Country</th>
                        <th>Region</th>
                        <th>Weather Records Count</th>
                        <th>Average Temperature</th>
                        <th>Average Humidity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php mysqli_data_seek($left_join_data, 0); ?>
                    <?php while ($row = mysqli_fetch_assoc($left_join_data)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['city_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['country']); ?></td>
                        <td><?php echo htmlspecialchars($row['region']); ?></td>
                        <td><?php echo $row['record_count'] ?? 0; ?></td>
                        <td><?php echo $row['avg_temp'] ? number_format($row['avg_temp'], 1) . '°C' : 'No Data'; ?></td>
                        <td><?php echo $row['avg_humidity'] ? number_format($row['avg_humidity'], 1) . '%' : 'No Data'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h3>➕ Add New Weather Record</h3>
            <form method="POST">
                <div class="input-group">
                    <label>📍 Location:</label>
                    <select name="location_id" required>
                        <?php mysqli_data_seek($locations, 0); ?>
                        <?php while($loc = mysqli_fetch_assoc($locations)): ?>
                            <option value="<?php echo $loc['id']; ?>"><?php echo htmlspecialchars($loc['city_name']) . ', ' . htmlspecialchars($loc['country']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label>🌡️ Temperature (°C):</label>
                    <input type="number" step="0.01" name="temperature" required>
                </div>
                <div class="input-group">
                    <label>💧 Humidity (%):</label>
                    <input type="number" step="0.01" name="humidity" required>
                </div>
                <div class="input-group">
                    <label>💨 Wind Speed (km/h):</label>
                    <input type="number" step="0.01" name="wind_speed" required>
                </div>
                <div class="input-group">
                    <label>🌧️ Precipitation (mm):</label>
                    <input type="number" step="0.01" name="precipitation" value="0">
                </div>
                <div class="input-group">
                    <label>☁️ Weather Condition:</label>
                    <select name="weather_condition">
                        <option>Sunny</option>
                        <option>Rainy</option>
                        <option>Cloudy</option>
                        <option>Partly Cloudy</option>
                        <option>Stormy</option>
                        <option>Foggy</option>
                        <option>Windy</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>📅 Recorded Date:</label>
                    <input type="date" name="recorded_date" required>
                </div>
                <div class="modal-buttons">
                    <button type="submit" name="add_weather" class="submit-btn">Save Record</button>
                    <button type="button" class="cancel-btn" onclick="closeAddModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>✏️ Edit Weather Record</h3>
            <form method="POST">
                <input type="hidden" name="record_id" id="edit_id">
                <div class="input-group">
                    <label>🌡️ Temperature (°C):</label>
                    <input type="number" step="0.01" name="temperature" id="edit_temp" required>
                </div>
                <div class="input-group">
                    <label>💧 Humidity (%):</label>
                    <input type="number" step="0.01" name="humidity" id="edit_humidity" required>
                </div>
                <div class="input-group">
                    <label>💨 Wind Speed (km/h):</label>
                    <input type="number" step="0.01" name="wind_speed" id="edit_wind" required>
                </div>
                <div class="input-group">
                    <label>🌧️ Precipitation (mm):</label>
                    <input type="number" step="0.01" name="precipitation" id="edit_precip">
                </div>
                <div class="input-group">
                    <label>☁️ Weather Condition:</label>
                    <select name="weather_condition" id="edit_condition">
                        <option>Sunny</option>
                        <option>Rainy</option>
                        <option>Cloudy</option>
                        <option>Partly Cloudy</option>
                        <option>Stormy</option>
                        <option>Foggy</option>
                        <option>Windy</option>
                    </select>
                </div>
                <div class="modal-buttons">
                    <button type="submit" name="update_weather" class="submit-btn">Update Record</button>
                    <button type="button" class="cancel-btn" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Initialize Charts
        const cities = <?php echo json_encode($cities); ?>;
        const temps = <?php echo json_encode($temps); ?>;
        const humidities = <?php echo json_encode($humidities); ?>;
        
        new Chart(document.getElementById('tempChart'), { 
            type: 'bar', 
            data: { 
                labels: cities, 
                datasets: [{ 
                    label: 'Temperature (°C)', 
                    data: temps, 
                    backgroundColor: '#667eea',
                    borderRadius: 8
                }] 
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });
        
        new Chart(document.getElementById('humidityChart'), { 
            type: 'line', 
            data: { 
                labels: cities, 
                datasets: [{ 
                    label: 'Humidity (%)', 
                    data: humidities, 
                    borderColor: '#764ba2',
                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
                    fill: true,
                    tension: 0.4
                }] 
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });

        // Modal Functions
        function openAddModal() { 
            document.getElementById('addModal').style.display = 'flex'; 
        }
        
        function closeAddModal() { 
            document.getElementById('addModal').style.display = 'none'; 
        }
        
        function openEditModal(id, temp, humidity, wind, precip, condition) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_temp').value = temp;
            document.getElementById('edit_humidity').value = humidity;
            document.getElementById('edit_wind').value = wind;
            document.getElementById('edit_precip').value = precip;
            document.getElementById('edit_condition').value = condition;
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeEditModal() { 
            document.getElementById('editModal').style.display = 'none'; 
        }

        // Search Functionality
        function searchTable() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let rows = document.querySelectorAll('#weatherTable tbody tr');
            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        }

        // Sort Functionality
        function sortTable() {
            let select = document.getElementById('sortSelect');
            let value = select.value;
            let rows = Array.from(document.querySelectorAll('#weatherTable tbody tr'));
            
            rows.sort((a, b) => {
                let aVal, bVal;
                if (value === 'city') {
                    aVal = a.cells[0].innerText;
                    bVal = b.cells[0].innerText;
                    return aVal.localeCompare(bVal);
                } else if (value === 'temp_asc') {
                    aVal = parseFloat(a.cells[3].innerText);
                    bVal = parseFloat(b.cells[3].innerText);
                    return aVal - bVal;
                } else if (value === 'temp_desc') {
                    aVal = parseFloat(a.cells[3].innerText);
                    bVal = parseFloat(b.cells[3].innerText);
                    return bVal - aVal;
                } else if (value === 'humidity') {
                    aVal = parseFloat(a.cells[4].innerText);
                    bVal = parseFloat(b.cells[4].innerText);
                    return bVal - aVal;
                } else {
                    return 0;
                }
            });
            
            let tbody = document.querySelector('#weatherTable tbody');
            rows.forEach(row => tbody.appendChild(row));
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>