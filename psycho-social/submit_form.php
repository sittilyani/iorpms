<?php
// Include database configuration
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
                // Begin transaction
                $pdo->beginTransaction();

                // Insert basic client information
                $stmt = $pdo->prepare("INSERT INTO clients (client_name, unique_id, sex, other_sex, visit_type, date, intake_date)
                                                            VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                        $_POST['client_name'],
                        $_POST['unique_id'],
                        $_POST['sex'],
                        $_POST['other_sex_specify'] ?? null,
                        $_POST['visit_type'],
                        $_POST['date'],
                        $_POST['intake_date']
                ]);

                $client_id = $pdo->lastInsertId();

                // Insert social history
                $stmt = $pdo->prepare("INSERT INTO social_history (client_id, marital_status, marital_other, living_arrangements,
                                                            living_arrangements_detail, living_other, previous_treatment, treatment_specify)
                                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                        $client_id,
                        $_POST['marital_status'],
                        $_POST['marital_other_specify'] ?? null,
                        $_POST['living_arrangements'],
                        implode(', ', $_POST['living_arrangements_detail'] ?? []),
                        $_POST['living_other_specify'] ?? null,
                        $_POST['previous_treatment'],
                        $_POST['treatment_specify'] ?? null
                ]);

                // Insert sexual history
                $stmt = $pdo->prepare("INSERT INTO sexual_history (client_id, sexually_active, sexual_partners, unprotected_sex)
                                                            VALUES (?, ?, ?, ?)");
                $stmt->execute([
                        $client_id,
                        $_POST['sexually_active'],
                        $_POST['sexual_partners'],
                        $_POST['unprotected_sex']
                ]);

                // Insert education and occupational history
                $stmt = $pdo->prepare("INSERT INTO education_occupation (client_id, education_level, education_other, has_income,
                                                            income_specify, employment_status, missed_work, fired_work)
                                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                        $client_id,
                        $_POST['education_level'],
                        $_POST['education_other_specify'] ?? null,
                        $_POST['has_income'],
                        $_POST['income_specify'] ?? null,
                        $_POST['employment_status'],
                        $_POST['missed_work'],
                        $_POST['fired_work']
                ]);

                // Insert family relationships
                $stmt = $pdo->prepare("INSERT INTO family_relationships (client_id, family_relationship, has_dependents,
                                                            dependents, dependent_other, has_support)
                                                            VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                        $client_id,
                        $_POST['family_relationship'],
                        $_POST['has_dependents'],
                        implode(', ', $_POST['dependents'] ?? []),
                        $_POST['dependent_other_specify'] ?? null,
                        $_POST['has_support']
                ]);

                // Commit transaction
                $pdo->commit();

                // Redirect to success page
                header('Location: form_success.php');
                exit;

        } catch (Exception $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                echo "Error: " . $e->getMessage();
        }
}
?>