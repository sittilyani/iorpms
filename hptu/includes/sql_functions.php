<?php
function fill_category_list($connect)
{
    $query = "
    SELECT * FROM category
    WHERE category_status = 'active'
    ORDER BY category_name ASC
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    $result = $statement->fetchAll();
    $output = '';
    foreach($result as $row)
    {
        $output .= '<option value="'.$row["category_id"].'">'.$row["category_name"].'</option>';
    }
    return $output;
}

function fill_brand_list($connect, $category_id)
{
    $query = "SELECT * FROM brand
    WHERE brand_status = 'active'
    AND category_id = '".$category_id."'
    ORDER BY brand_name ASC";
    $statement = $connect->prepare($query);
    $statement->execute();
    $result = $statement->fetchAll();
    $output = '<option value="">Select Brand</option>';
    foreach($result as $row)
    {
        $output .= '<option value="'.$row["brand_id"].'">'.$row["brand_name"].'</option>';
    }
    return $output;
}

function get_username($connect, $user_id)
{
    $query = "
    SELECT username FROM user_details WHERE user_id = '".$user_id."'
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    $result = $statement->fetchAll();
    foreach($result as $row)
    {
        return $row['username'];
    }
}

function fill_product_list($connect)
{
    $query = "
    SELECT * FROM product
    WHERE product_status = 'active'
    ORDER BY product_name ASC
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    $result = $statement->fetchAll();
    $output = '';
    foreach($result as $row)
    {
        $output .= '<option value="'.$row["product_id"].'">'.$row["product_name"].'</option>';
    }
    return $output;
}

   function fetch_product_details($product_id, $connect)
{
    $query = "
    SELECT * FROM product
    WHERE product_id = '".$product_id."'";
    $statement = $connect->prepare($query);
    $statement->execute();
    return $statement->fetch(PDO::FETCH_ASSOC);
    foreach($result as $row)
    {
        $output['product_name'] = $row["product_name"];
        $output['quantity'] = $row["product_quantity"];
        $output['price'] = $row['product_base_price'];
        $output['tax'] = $row['product_tax'];
    }
    return $output;
}

function available_product_quantity($connect, $product_id)
{
    $product_data = fetch_product_details($product_id, $connect);
    $query = "
    SELECT 	inventory_order_product.quantity FROM inventory_order_product
    INNER JOIN inventory_order ON inventory_order.inventory_order_id = inventory_order_product.inventory_order_id
    WHERE inventory_order_product.product_id = '".$product_id."' AND
    inventory_order.inventory_order_status = 'active'
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    $result = $statement->fetchAll();
    $total = 0;
    foreach($result as $row)
    {
        $total = $total + $row['quantity'];
    }
    $available_quantity = intval($product_data['quantity']) - intval($total);
    if($available_quantity == 0)
    {
        $update_query = "
        UPDATE product SET
        product_status = 'inactive'
        WHERE product_id = '".$product_id."'
        ";
        $statement = $connect->prepare($update_query);
        $statement->execute();
    }
    return $available_quantity;
}
function fetch_product_details_with_relations($product_id, $connect) {
    $query = "
    SELECT p.*, c.category_name, b.brand_name
    FROM product p
    LEFT JOIN category c ON p.category_id = c.category_id
    LEFT JOIN brand b ON p.brand_id = b.brand_id
    WHERE p.product_id = :product_id
    ";
    $statement = $connect->prepare($query);
    $statement->execute([':product_id' => $product_id]);
    return $statement->fetch(PDO::FETCH_ASSOC);
}

/**
 * Checks if a product exists
 */
function product_exists($product_id, $connect) {
    $query = "SELECT COUNT(*) FROM product WHERE product_id = :product_id";
    $statement = $connect->prepare($query);
    $statement->execute([':product_id' => $product_id]);
    return $statement->fetchColumn() > 0;
}

/**
 * INVENTORY RELATED FUNCTIONS
 */

/**
 * Gets low stock products (below specified threshold)
 */
function get_low_stock_products($connect, $threshold = 5) {
    $query = "
    SELECT p.product_id, p.product_name, p.product_quantity, p.product_unit
    FROM product p
    WHERE p.product_quantity <= :threshold
    AND p.product_status = 'active'
    ORDER BY p.product_quantity ASC
    ";
    $statement = $connect->prepare($query);
    $statement->execute([':threshold' => $threshold]);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Gets recently added products
 */
function get_recent_products($connect, $limit = 5) {
    $query = "
    SELECT p.product_id, p.product_name, p.product_quantity, p.product_unit,
           p.product_base_price, p.product_enter_date
    FROM product p
    WHERE p.product_status = 'active'
    ORDER BY p.product_enter_date DESC
    LIMIT :limit
    ";
    $statement = $connect->prepare($query);
    $statement->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ORDER RELATED FUNCTIONS
 */

/**
 * Gets order details with customer information
 */
function get_order_details($order_id, $connect) {
    $query = "
    SELECT o.*,
           GROUP_CONCAT(p.product_name SEPARATOR ', ') as products,
           COUNT(op.product_id) as product_count,
           u.username as entered_by
    FROM inventory_order o
    LEFT JOIN inventory_order_product op ON o.inventory_order_id = op.inventory_order_id
    LEFT JOIN product p ON op.product_id = p.product_id
    LEFT JOIN user_details u ON o.user_id = u.user_id
    WHERE o.inventory_order_id = :order_id
    GROUP BY o.inventory_order_id
    ";
    $statement = $connect->prepare($query);
    $statement->execute([':order_id' => $order_id]);
    return $statement->fetch(PDO::FETCH_ASSOC);
}

/**
 * Gets today's orders summary
 */
function get_todays_orders_summary($connect) {
    $query = "
    SELECT
        COUNT(*) as total_orders,
        SUM(inventory_order_total) as total_value,
        SUM(CASE WHEN payment_status = 'cash' THEN inventory_order_total ELSE 0 END) as cash_total,
        SUM(CASE WHEN payment_status = 'credit' THEN inventory_order_total ELSE 0 END) as credit_total,
        SUM(CASE WHEN payment_status = 'mpesa' THEN inventory_order_total ELSE 0 END) as mpesa_total
    FROM inventory_order
    WHERE DATE(inventory_order_date) = CURDATE()
    AND inventory_order_status = 'active'
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    return $statement->fetch(PDO::FETCH_ASSOC);
}

/**
 * USER RELATED FUNCTIONS
 */

/**
 * Gets user details with role information
 */
function get_user_details($user_id, $connect) {
    $query = "
    SELECT u.*, r.role_name
    FROM user_details u
    LEFT JOIN role r ON u.role = r.role_id
    WHERE u.user_id = :user_id
    ";
    $statement = $connect->prepare($query);
    $statement->execute([':user_id' => $user_id]);
    return $statement->fetch(PDO::FETCH_ASSOC);
}

/**
 * Checks if a username already exists
 */
function username_exists($username, $connect, $exclude_user_id = null) {
    $query = "SELECT COUNT(*) FROM user_details WHERE username = :username";
    if ($exclude_user_id) {
        $query .= " AND user_id != :exclude_user_id";
    }

    $statement = $connect->prepare($query);
    $params = [':username' => $username];
    if ($exclude_user_id) {
        $params[':exclude_user_id'] = $exclude_user_id;
    }

    $statement->execute($params);
    return $statement->fetchColumn() > 0;
}

/**
 * UTILITY FUNCTIONS
 */

/**
 * Sanitizes input data
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Formats currency
 */
function format_currency($amount) {
    return 'KES ' . number_format($amount, 2);
}

/**
 * Generates a random string (for passwords, tokens, etc.)
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters)) - 1];
    }
    return $randomString;
}


/**
 * Gets sales data for charts (last 30 days)
 */
function get_sales_chart_data($connect) {
    $query = "
    SELECT
        DATE(inventory_order_date) as date,
        SUM(inventory_order_total) as total_sales,
        COUNT(*) as order_count
    FROM inventory_order
    WHERE inventory_order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    AND inventory_order_status = 'active'
    GROUP BY DATE(inventory_order_date)
    ORDER BY DATE(inventory_order_date) ASC
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Gets top selling products
 */
function get_top_selling_products($connect, $limit = 5) {
    $query = "
    SELECT
        p.product_id,
        p.product_name,
        SUM(op.quantity) as total_quantity,
        SUM(op.quantity * op.price) as total_value
    FROM inventory_order_product op
    JOIN product p ON op.product_id = p.product_id
    JOIN inventory_order o ON op.inventory_order_id = o.inventory_order_id
    WHERE o.inventory_order_status = 'active'
    GROUP BY op.product_id
    ORDER BY total_quantity DESC
    LIMIT :limit
    ";
    $statement = $connect->prepare($query);
    $statement->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * Counts the total number of users.
 */
function count_total_user($connect) {
    $query = "SELECT COUNT(*) FROM users";
    $statement = $connect->prepare($query);
    $statement->execute();
    return $statement->fetchColumn();
}

/**
 * Counts the total number of categories.
 */
function count_total_category($connect) {
    $query = "SELECT COUNT(*) FROM category";
    $statement = $connect->prepare($query);
    $statement->execute();
    return $statement->fetchColumn();
}

/**
 * Counts the total number of brands.
 */
function count_total_brand($connect) {
    $query = "SELECT COUNT(*) FROM brand";
    $statement = $connect->prepare($query);
    $statement->execute();
    return $statement->fetchColumn();
}

/**
 * Counts the total number of active products in stock.
 */
function count_total_product($connect) {
    $query = "SELECT COUNT(*) FROM product WHERE product_status = 'active'";
    $statement = $connect->prepare($query);
    $statement->execute();
    return $statement->fetchColumn();
}

/**
 * Calculates the total value of all active orders.
 */
function count_total_order_value($connect) {
    $query = "SELECT SUM(inventory_order_total) FROM inventory_order WHERE inventory_order_status = 'active'";
    $statement = $connect->prepare($query);
    $statement->execute();
    return $statement->fetchColumn() ?: 0; // Use 0 if SUM is NULL
}

/**
 * Calculates the total value of all active cash orders.
 */
function count_total_cash_order_value($connect) {
    $query = "SELECT SUM(inventory_order_total) FROM inventory_order WHERE inventory_order_status = 'active' AND payment_status = 'cash'";
    $statement = $connect->prepare($query);
    $statement->execute();
    return $statement->fetchColumn() ?: 0;
}

/**
 * Calculates the total value of all active credit orders.
 */
function count_total_credit_order_value($connect) {
    $query = "SELECT SUM(inventory_order_total) FROM inventory_order WHERE inventory_order_status = 'active' AND payment_status = 'credit'";
    $statement = $connect->prepare($query);
    $statement->execute();
    return $statement->fetchColumn() ?: 0;
}

/**
 * Gets the total order value user-wise.
 */
function get_user_wise_total_order($connect) {
    $query = "
    SELECT ud.username, SUM(io.inventory_order_total) AS total_order_value
    FROM inventory_order io
    JOIN user_details ud ON io.user_id = ud.user_id
    WHERE io.inventory_order_status = 'active'
    GROUP BY ud.username
    ORDER BY total_order_value DESC
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    $output = '';
    if ($result) {
        $output .= '<table class="table table-bordered">';
        $output .= '<thead><tr><th>User</th><th>Total Order Value</th></tr></thead>';
        $output .= '<tbody>';
        foreach ($result as $row) {
            $output .= '<tr><td>' . htmlspecialchars($row['username']) . '</td><td>' . format_currency($row['total_order_value']) . '</td></tr>';
        }
        $output .= '</tbody></table>';
    } else {
        $output .= '<p>No orders found.</p>';
    }
    return $output;
}
?>