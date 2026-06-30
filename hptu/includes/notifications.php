<?php
// includes/notifications.php

// Define allowed roles in lowercase once for case-insensitive checks
$allowed_approver_roles = [
    'Supervisor', 'Manager', 'Admin','County Nursing Officer', 'County Medical Laboratory Coordinator',
    'Sub County Nutritionist', 'Sub County Public Health Nurse', 'Sub County Nursing Officer',
    'Sub County Medical Laboratory Coordinator', 'Sub County Pharmacist', 'County Pharmacist/HPTU Lead'
    // Ensure all other approver roles are included here in lowercase
];

// Define roles allowed to ISSUE commodities (Facility Staff and possibly others) in lowercase
$allowed_issuer_roles = ['Sub County Nutritionist', 'Sub County Public Health Nurse',
                        'Sub County Nursing Officer',
                        'Sub County Medical Laboratory Coordinator', 'Sub County Pharmacist'
    // Include any other roles that handle stock issuance at the facility level in lowercase
];


/**
 * Retrieves counts for pending approvals and approved requests ready for issuance.
 * * NOTE: This function's signature must be matched in header.php when it's called.
 */
function getNotificationCounts($conn, $user_id, $userrole) {
    global $allowed_approver_roles, $allowed_issuer_roles;

    $counts = [
        'pending_approvals' => 0,
        'approved_for_issuance' => 0, // Key for Facility Staff notification
        'total_unread' => 0
    ];

    // Convert current user role to lowercase once for all checks
    $userrole_lower = strtolower($userrole);

    // --- 1. Notification for APPROVERS (Pending Requests) ---
    if (in_array($userrole_lower, $allowed_approver_roles)) {

        // Base query for pending distribution requests
        $approval_sql = "SELECT COUNT(*) as count FROM distribution_requests WHERE status = 'pending'";

        $result = false; // Initialize result variable

        // For sub-county approvers, filter by their subcounty
        if (strpos($userrole_lower, 'sub county') !== false && !empty($user_subcounty)) {
            // Using prepared statement for safety
            $approval_stmt = $conn->prepare($approval_sql . " AND requesting_subcounty = ?");
            $approval_stmt->bind_param("s", $user_subcounty);
            $approval_stmt->execute();
            $result = $approval_stmt->get_result();
            $approval_stmt->close();
        } else {
             // For County/Admin approvers (no subcounty filter needed)
            $result = $conn->query($approval_sql);
        }

        if ($result) {
            $counts['pending_approvals'] = $result->fetch_assoc()['count'] ?? 0;
        }
    }

    // --- 2. Notification for ISSUERS (Approved Requests ready for Issuance) ---
    // Facility Staff/Issuer needs to see requests that are APPROVED AND where their facility is the SOURCE_FACILITY (the issuing facility).
    if (in_array($userrole_lower, $allowed_issuer_roles) && !empty($user_facility)) {

        $issuance_sql = "SELECT COUNT(*) as count
                         FROM distribution_request_items
                         WHERE status = 'approved'
                         AND source_facility = ?"; // Filters by issuing facility

        // Using prepared statement for safety
        $issuance_stmt = $conn->prepare($issuance_sql);
        $issuance_stmt->bind_param("s", $user_facility);
        $issuance_stmt->execute();
        $result = $issuance_stmt->get_result();

        if ($result) {
            $counts['approved_for_issuance'] = $result->fetch_assoc()['count'] ?? 0;
        }
        $issuance_stmt->close();
    }


    // --- 3. Total Unread System Notifications (Generic) ---
    // Using prepared statement for safety
    $unread_sql = "SELECT COUNT(*) as count
                   FROM system_notifications
                   WHERE (user_id = ? OR userrole = ?)
                   AND is_read = FALSE";

    $unread_stmt = $conn->prepare($unread_sql);
    // Use the original $userrole here as system_notifications table might store roles case-sensitive
    $unread_stmt->bind_param("is", $user_id, $userrole);
    $unread_stmt->execute();
    $result = $unread_stmt->get_result();

    if ($result) {
        $counts['total_unread'] = $result->fetch_assoc()['count'] ?? 0;
    }
    $unread_stmt->close();


    return $counts;
}

// NOTE: The following helper functions are kept as originally provided by the user.

function getNotifications($conn, $user_id, $userrole, $limit = 10) {
    // This function should ideally also use prepared statements for $userrole and $user_id
    $sql = "SELECT * FROM system_notifications
            WHERE (user_id = $user_id OR userrole = '$userrole')
            ORDER BY created_at DESC
            LIMIT $limit";

    $result = $conn->query($sql);
    $notifications = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }

    return $notifications;
}

function markNotificationAsRead($conn, $notification_id, $user_id) {
    $sql = "UPDATE system_notifications
            SET is_read = TRUE
            WHERE id = ? AND user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $user_id);
    return $stmt->execute();
}

function markAllNotificationsAsRead($conn, $user_id, $userrole) {
    $sql = "UPDATE system_notifications
            SET is_read = TRUE
            WHERE (user_id = ? OR userrole = ?)
            AND is_read = FALSE";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $userrole);
    return $stmt->execute();
}
?>