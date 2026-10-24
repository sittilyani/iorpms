<?php
// The `products` table is retired: drugs, prices and stock all use the `drug` table. Existing stock rows (stocks.drugID held a
// products id) are pointed at the drug of the same name, then products is dropped. Safe to re-run.
$t = $conn->query("SHOW TABLES LIKE 'products'");
if ($t && $t->num_rows) {
    $rows = $conn->query("SELECT TRIM(p.productname) n, p.brandname, p.price FROM products p WHERE NOT EXISTS (SELECT 1 FROM drug d WHERE LOWER(TRIM(d.drugName)) = LOWER(TRIM(p.productname)))");
    foreach ($rows ? $rows->fetch_all(MYSQLI_ASSOC) : [] as $r) {
        $st = $conn->prepare("INSERT INTO drug (drugName, drugCategory, description, price) VALUES (?, 'Medicines', ?, ?)");
        $d = (string)$r['brandname']; $pr = (float)$r['price']; $st->bind_param('ssd', $r['n'], $d, $pr); $st->execute();
    }
    $conn->query("UPDATE stocks s JOIN products p ON p.id = s.drugID JOIN (SELECT LOWER(TRIM(drugName)) k, MIN(drugID) id FROM drug GROUP BY k) d ON d.k = LOWER(TRIM(p.productname)) SET s.drugID = d.id");
    $orphans = $conn->query("SELECT COUNT(*) FROM stocks s WHERE NOT EXISTS (SELECT 1 FROM drug d WHERE d.drugID = s.drugID)")->fetch_row()[0];
    if ((int)$orphans === 0) { $conn->query("DROP TABLE products"); }
}
