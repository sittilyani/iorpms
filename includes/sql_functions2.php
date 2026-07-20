                <?php
                    // $conn is already open from the page's initial config.php include.
                    // Do NOT close and re-open the connection between queries.

                    $startDate = date("Y-m-01");
                    $endDate   = date("Y-m-t");

                    // --- Methadone dispensed this month ---
                    $sql  = "SELECT SUM(dosage) AS methadone_total_dosage FROM pharmacy WHERE DATE(visitDate) BETWEEN ? AND ? AND drugName = 'Methadone'";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("ss", $startDate, $endDate);
                        $stmt->execute();
                        $row = $stmt->get_result()->fetch_assoc();
                        echo '<p>' . (function_exists('tf') ? tf('tpl_disp_month', ['{drug}' => 'Methadone']) : 'Methadone Dispensed in the Month:') . ' <span style="font-weight: bold; color: #0033CC;">' . ($row['methadone_total_dosage'] ?? 0) . '&nbsp;mg</span></p>';
                        $stmt->close();
                    } else {
                        echo "Error: " . $conn->error;
                    }

                    // --- Buprenorphine 2mg dispensed this month ---
                    $sql  = "SELECT SUM(dosage) AS bupren2_total_dosage FROM pharmacy WHERE DATE(visitDate) BETWEEN ? AND ? AND drugName = 'Buprenorphine 2mg'";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("ss", $startDate, $endDate);
                        $stmt->execute();
                        $row = $stmt->get_result()->fetch_assoc();
                        echo '<p>' . (function_exists('tf') ? tf('tpl_disp_month', ['{drug}' => 'Buprenorphine 2mg']) : 'Buprenorphine 2mg Dispensed in the Month:') . ' <span style="font-weight: bold; color: #0033CC;">' . ($row['bupren2_total_dosage'] ?? 0) . '&nbsp;mg</span></p>';
                        $stmt->close();
                    } else {
                        echo "Error: " . $conn->error;
                    }

                    // --- Buprenorphine 8mg dispensed this month ---
                    $sql  = "SELECT SUM(dosage) AS bupren8_total_dosage FROM pharmacy WHERE DATE(visitDate) BETWEEN ? AND ? AND drugName = 'Buprenorphine 8mg'";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("ss", $startDate, $endDate);
                        $stmt->execute();
                        $row = $stmt->get_result()->fetch_assoc();
                        echo '<p>' . (function_exists('tf') ? tf('tpl_disp_month', ['{drug}' => 'Buprenorphine 8mg']) : 'Buprenorphine 8mg Dispensed in the Month:') . ' <span style="font-weight: bold; color: #0033CC;">' . ($row['bupren8_total_dosage'] ?? 0) . '&nbsp;mg</span></p>';
                        $stmt->close();
                    } else {
                        echo "Error: " . $conn->error;
                    }
                    ?>
