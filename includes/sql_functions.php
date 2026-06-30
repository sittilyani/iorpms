                <?php
                    // $conn is already open from the page's initial config.php include.
                    // Do NOT close and re-open the connection between queries.

                    // --- Methadone balance ---
                    $sql = "SELECT stock_movements.total_qty AS methadone_total_qty
                                FROM stock_movements
                                JOIN drug ON stock_movements.drugName = drug.drugName AND stock_movements.drugID = drug.drugID
                                WHERE drug.drugID = 2
                                AND drug.drugName = 'Methadone'
                                ORDER BY stock_movements.trans_date DESC
                                LIMIT 1";

                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        echo '<p>Methadone Balance: <span style="font-weight: bold; color: #0033CC;">' . $row['methadone_total_qty'] . '&nbsp;mg</span> <span style="font-weight: bold; color: red;">(' . ($row['methadone_total_qty'] / 5) . ' mL)</span></p>';
                    } else {
                        echo '<p>No Methadone stock records found.</p>';
                    }

                    // --- Buprenorphine 2mg balance ---
                    $sql = "SELECT stock_movements.total_qty AS bupren2_total_qty
                                FROM stock_movements
                                JOIN drug ON stock_movements.drugName = drug.drugName AND stock_movements.drugID = drug.drugID
                                WHERE drug.drugID = 6
                                AND drug.drugName = 'Buprenorphine 2mg'
                                ORDER BY stock_movements.trans_date DESC
                                LIMIT 1";

                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        echo '<p>Buprenor 2mg Bal: <span style="font-weight: bold; color: #0033CC;">' . $row['bupren2_total_qty'] . '&nbsp;mg</span></p>';
                    } else {
                        echo '<p>No Buprenor 2mg stock records found.</p>';
                    }

                    // --- Buprenorphine 4mg balance ---
                    $sql = "SELECT stock_movements.total_qty AS bupren4_total_qty
                                FROM stock_movements
                                JOIN drug ON stock_movements.drugName = drug.drugName AND stock_movements.drugID = drug.drugID
                                WHERE drug.drugID = 7
                                AND drug.drugName = 'Buprenorphine 4mg'
                                ORDER BY stock_movements.trans_date DESC
                                LIMIT 1";

                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        echo '<p>Buprenor 4mg Bal: <span style="font-weight: bold; color: #0033CC;">' . $row['bupren4_total_qty'] . '&nbsp;mg</span></p>';
                    } else {
                        echo '<p>No Buprenor 4mg stock records found.</p>';
                    }

                    // --- Buprenorphine 8mg balance ---
                    $sql = "SELECT stock_movements.total_qty AS bupren8_total_qty
                                FROM stock_movements
                                JOIN drug ON stock_movements.drugName = drug.drugName AND stock_movements.drugID = drug.drugID
                                WHERE drug.drugID = 8
                                AND drug.drugName = 'Buprenorphine 8mg'
                                ORDER BY stock_movements.trans_date DESC
                                LIMIT 1";

                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        echo '<p>Buprenor 8mg Bal: <span style="font-weight: bold; color: #0033CC;">' . $row['bupren8_total_qty'] . '&nbsp;mg</span></p>';
                    } else {
                        echo '<p>No Buprenor 8mg stock records found.</p>';
                    }
                    ?>
