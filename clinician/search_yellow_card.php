<?php
session_start();
include('../includes/config.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['userrole'])) {
    header('Location: ../public/signout.php');
    exit;
}

$page_title = 'Search Client for Yellow Card Visit';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #50b89a;
            --dark-color: #2C3162;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --border-color: #dcdcdc;
            --success-color: #4caf50;
            --danger-color: #d32f2f;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: none;
            padding: 20px;
        }

        .container {
            max-width: 80%;
            margin: 0 auto;
        }

        .search-card {
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 40px;
            margin-bottom: 30px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            color: var(--dark-color);
            font-size: 32px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
            font-size: 16px;
        }

        .search-box {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 300px;
            padding: 15px 20px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .search-btn {
            padding: 15px 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 144, 226, 0.3);
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .results-table thead {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
        }

        .results-table th,
        .results-table td {
            padding: 15px;
            text-align: left;
        }

        .results-table tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s ease;
        }

        .results-table tbody tr:hover {
            background-color: #f5f5f5;
        }

        .action-btn {
            padding: 8px 20px;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: #3a7bc8;
            transform: translateY(-1px);
        }

        .action-btn.view {
            background: var(--success-color);
        }

        .action-btn.view:hover {
            background: #388e3c;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 18px;
        }

        .back-btn {
            padding: 10px 25px;
            background: var(--danger-color);
            color: var(--white);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #b71c1c;
            transform: translateX(-3px);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-info {
            background: #e3f2fd;
            color: #1565c0;
            border-left: 4px solid #1565c0;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-btn">
            <i class="fas fa-arrow-left"></i> Go Back
        </a>

        <div class="search-card">
            <div class="page-header">
                <h1><i class="fas fa-file-medical"></i> Yellow Card Clinical Visit</h1>
                <p>Search for a patient to record clinical follow-up visit (Form 3C)</p>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <span>Search by MAT ID, MAT Number, Client Name, or Nickname</span>
            </div>

            <form method="GET" action="">
                <div class="search-box">
                    <input
                        type="text"
                        name="search"
                        class="search-input"
                        placeholder="Enter MAT ID, MAT Number, Client Name, or Nickname..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                        autofocus
                    >
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search Patient
                    </button>
                </div>
            </form>

            <?php
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $search = $conn->real_escape_string($_GET['search']);

                // Search query
                $sql = "SELECT
                            p.p_id,
                            p.mat_id,
                            p.mat_number,
                            p.clientName,
                            p.nickName,
                            p.age,
                            p.sex,
                            p.drugname,
                            p.dosage,
                            p.current_status
                        FROM patients p
                        WHERE
                            p.mat_id LIKE '%$search%' OR
                            p.mat_number LIKE '%$search%' OR
                            p.clientName LIKE '%$search%' OR
                            p.nickName LIKE '%$search%'
                        ORDER BY p.clientName ASC
                        LIMIT 20";

                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    echo '<table class="results-table">
                            <thead>
                                <tr>
                                    <th>MAT ID</th>
                                    <th>MAT Number</th>
                                    <th>Client Name</th>
                                    <th>Nickname</th>
                                    <th>Age</th>
                                    <th>Sex</th>
                                    <th>Drug</th>
                                    <th>Dosage</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>';

                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>
                                <td>' . htmlspecialchars($row['mat_id']) . '</td>
                                <td>' . htmlspecialchars($row['mat_number']) . '</td>
                                <td>' . htmlspecialchars($row['clientName']) . '</td>
                                <td>' . htmlspecialchars($row['nickName']) . '</td>
                                <td>' . htmlspecialchars($row['age']) . '</td>
                                <td>' . htmlspecialchars($row['sex']) . '</td>
                                <td>' . htmlspecialchars($row['drugname']) . '</td>
                                <td>' . htmlspecialchars($row['dosage']) . '</td>
                                <td>' . htmlspecialchars($row['current_status']) . '</td>
                                <td>
                                    <a href="yellow_card_form.php?mat_id=' . urlencode($row['mat_id']) . '" class="action-btn">
                                        <i class="fas fa-plus"></i> New Visit
                                    </a>
                                    <a href="view_yellow_card.php?mat_id=' . urlencode($row['mat_id']) . '" class="action-btn view">
                                        <i class="fas fa-eye"></i> View Records
                                    </a>
                                </td>
                            </tr>';
                    }

                    echo '</tbody></table>';
                } else {
                    echo '<div class="no-results">
                            <i class="fas fa-search" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                            <p>No patients found matching your search.</p>
                            <p style="font-size: 14px; color: #999;">Try searching with different keywords.</p>
                        </div>';
                }
            }
            ?>
        </div>
    </div>
</body>
</html>