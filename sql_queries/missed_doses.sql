-- This selects all avaialble dates

SELECT DATE(ph.visitDate) AS pharmacy_date
FROM pharmacy ph
WHERE ph.mat_id = 'C28293MAT0018'  -- Replace 'specific_mat_id' with the actual mat_id from the URL
AND DATE(ph.visitDate) BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND DATE_SUB(CURDATE(), INTERVAL 1 DAY)
ORDER BY pharmacy_date;

-- This selects the missing dates

SELECT DISTINCT DATE(ph.visitDate) AS missed_date
FROM patients p
LEFT JOIN pharmacy ph ON p.mat_id = ph.mat_id AND DATE(ph.visitDate) BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND DATE_SUB(CURDATE(), INTERVAL 1 DAY)
WHERE ph.visitDate IS NULL
AND p.mat_id = 'C28293MAT0018'  -- Replace 'specific_mat_id' with the actual mat_id from the URL
ORDER BY missed_date;


--This also displays missing dates

SELECT DATE(dates.date) AS MissingDate
FROM (
    SELECT @date := @date + INTERVAL 1 DAY AS date
    FROM (
        SELECT @date := '2024-04-01' -- Start date
    ) sql1
    CROSS JOIN (
        SELECT @max := '2024-05-10' -- End date
    ) sql2
    WHERE @date <= @max
) dates
LEFT JOIN pharmacy p ON p.visitDate = dates.date AND p.mat_id = (
    SELECT p_id
    FROM patients
    WHERE mat_id = 'C28293MAT0018'
)
WHERE p.visitDate IS NULL;

-- Counting the missed days
SELECT
COUNT(*) AS num_rows,
DATEDIFF(CURDATE(), STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-01'), '%Y-%m-%d')) - COUNT(*) AS new_num_rows
FROM
patients p
JOIN
pharmacy ph ON p.mat_id = ph.mat_id
WHERE
p.mat_id = 'C28293MAT0016'
AND ph.dosage > 0;