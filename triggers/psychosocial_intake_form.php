<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medically Assisted Therapy Psycho-Social Intake Form</title>
    <style>
        /* CSS Styles */
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .form-section {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 1.1em;
            color: #2c3e50;
            padding-left: 10px;
            border-left: 4px solid #3498db;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="date"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }

        .checkbox-item input {
            margin-right: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .btn-submit {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            margin: 20px auto 0;
        }

        .btn-submit:hover {
            background-color: #2980b9;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .form-col {
            flex: 1;
            min-width: 200px;
        }

        .hidden {
            display: none;
        }

        .required::after {
            content: " *";
            color: red;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }

            .form-col {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>MEDICALLY ASSISTED THERAPY PSYCHO-SOCIAL INTAKE & FOLLOW-UP FORM - FORM 2A</h1>

        <form id="intakeForm" method="POST" action="submit_form.php">
            <!-- Visit Type and Date Section -->
            <div class="form-section">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="date" class="required">Date (dd/mm/yyyy)</label>
                            <input type="date" id="date" name="date" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>Visit Type</label>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="radio" id="initial" name="visit_type" value="initial" checked>
                                    <label for="initial">Initial</label>
                                </div>
                                <?php for($i = 1; $i <= 8; $i++): ?>
                                <div class="checkbox-item">
                                    <input type="radio" id="followup<?php echo $i; ?>" name="visit_type" value="followup<?php echo $i; ?>">
                                    <label for="followup<?php echo $i; ?>">Follow up <?php echo $i; ?></label>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic Information Section -->
            <div class="form-section">
                <div class="section-title">1. Client Information</div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="client_name" class="required">Client Name in Full</label>
                            <input type="text" id="client_name" name="client_name" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="unique_id" class="required">Unique ID</label>
                            <input type="text" id="unique_id" name="unique_id" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>Sex</label>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="radio" id="male" name="sex" value="male">
                                    <label for="male">Male</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="radio" id="female" name="sex" value="female">
                                    <label for="female">Female</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="radio" id="other_sex" name="sex" value="other">
                                    <label for="other_sex">Other (Specify)</label>
                                    <input type="text" id="other_sex_specify" name="other_sex_specify" class="hidden">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social History Section -->
            <div class="form-section">
                <div class="section-title">4. SOCIAL HISTORY</div>

                <div class="form-group">
                    <label for="intake_date" class="required">a. Date of intake interview (dd/mm/yyyy)</label>
                    <input type="date" id="intake_date" name="intake_date" required>
                </div>

                <div class="form-group">
                    <label>b. Marital status?</label>
                    <div class="checkbox-group">
                        <?php
                        $marital_statuses = ["Married", "Remarried", "Widowed", "Separated", "Divorced", "Never Married", "Other"];
                        foreach($marital_statuses as $status):
                        ?>
                        <div class="checkbox-item">
                            <input type="radio" id="marital_<?php echo strtolower(str_replace(' ', '_', $status)); ?>" name="marital_status" value="<?php echo $status; ?>">
                            <label for="marital_<?php echo strtolower(str_replace(' ', '_', $status)); ?>"><?php echo $status; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" id="marital_other_specify" name="marital_other_specify" placeholder="Specify other" class="hidden">
                </div>

                <div class="form-group">
                    <label>c. How would you describe your usual living arrangements (past 3 months)?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="stable_arrangement" name="living_arrangements" value="stable">
                            <label for="stable_arrangement">Stable arrangement</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="no_stable_arrangement" name="living_arrangements" value="no_stable">
                            <label for="no_stable_arrangement">No stable arrangement</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>d. Usual living arrangements in the past 3 months? (tick all that apply)</label>
                    <div class="checkbox-group">
                        <?php
                        $living_options = [
                            "Family house", "Friend's house", "Streets", "Abandoned buildings",
                            "Public areas", "Parks", "Bus stations", "Tunnels", "Injecting site", "Other"
                        ];
                        foreach($living_options as $option):
                        ?>
                        <div class="checkbox-item">
                            <input type="checkbox" id="living_<?php echo strtolower(str_replace([' ', "'"], ['_', ''], $option)); ?>" name="living_arrangements_detail[]" value="<?php echo $option; ?>">
                            <label for="living_<?php echo strtolower(str_replace([' ', "'"], ['_', ''], $option)); ?>"><?php echo $option; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" id="living_other_specify" name="living_other_specify" placeholder="Specify other" class="hidden">
                </div>

                <div class="form-group">
                    <label>e. Have you ever been treated for Mental illness or substance use disorder before?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="treatment_yes" name="previous_treatment" value="yes">
                            <label for="treatment_yes">Yes</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="treatment_no" name="previous_treatment" value="no">
                            <label for="treatment_no">No</label>
                        </div>
                    </div>
                    <input type="text" id="treatment_specify" name="treatment_specify" placeholder="If Yes, Specify" class="hidden">
                </div>
            </div>

            <!-- Sexual History Section -->
            <div class="form-section">
                <div class="section-title">5. SEXUAL HISTORY</div>

                <div class="form-group">
                    <label>a. Have you been sexually active in the last 3 months?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="sex_active_yes" name="sexually_active" value="yes">
                            <label for="sex_active_yes">Yes</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="sex_active_no" name="sexually_active" value="no">
                            <label for="sex_active_no">No</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>b. Number of sexual partners in the last 3 months?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="single_partner" name="sexual_partners" value="single">
                            <label for="single_partner">Single</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="multiple_partners" name="sexual_partners" value="multiple">
                            <label for="multiple_partners">Multiple</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>c. Have you had unprotected sex in the last 3 months?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="unprotected_yes" name="unprotected_sex" value="yes">
                            <label for="unprotected_yes">Yes</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="unprotected_no" name="unprotected_sex" value="no">
                            <label for="unprotected_no">No</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Education and Occupational History Section -->
            <div class="form-section">
                <div class="section-title">6. EDUCATION AND OCCUPATIONAL HISTORY</div>

                <div class="form-group">
                    <label>a. What is your highest level of education</label>
                    <div class="checkbox-group">
                        <?php
                        $education_levels = ["None", "Primary", "Secondary", "Post-secondary", "Other"];
                        foreach($education_levels as $level):
                        ?>
                        <div class="checkbox-item">
                            <input type="radio" id="education_<?php echo strtolower($level); ?>" name="education_level" value="<?php echo $level; ?>">
                            <label for="education_<?php echo strtolower($level); ?>"><?php echo $level; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" id="education_other_specify" name="education_other_specify" placeholder="Specify other" class="hidden">
                </div>

                <div class="form-group">
                    <label>b. Do you have a source of income?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="income_yes" name="has_income" value="yes">
                            <label for="income_yes">Yes</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="income_no" name="has_income" value="no">
                            <label for="income_no">No</label>
                        </div>
                    </div>
                    <input type="text" id="income_specify" name="income_specify" placeholder="If Yes, Specify" class="hidden">
                </div>

                <div class="form-group">
                    <label>c. What is your employment status in the past 12 months?</label>
                    <div class="checkbox-group">
                        <?php
                        $employment_statuses = [
                            "Employed Full time (40hrs)", "Employment Part time (<40hrs)",
                            "Unemployed (Currently looking for work)", "Unemployed (Currently not looking for work)",
                            "Student", "Retired", "Self-employed"
                        ];
                        foreach($employment_statuses as $status):
                        ?>
                        <div class="checkbox-item">
                            <input type="radio" id="employment_<?php echo strtolower(str_replace([' ', '(', ')', '<'], ['_', '', '', ''], $status)); ?>" name="employment_status" value="<?php echo $status; ?>">
                            <label for="employment_<?php echo strtolower(str_replace([' ', '(', ')', '<'], ['_', '', '', ''], $status)); ?>"><?php echo $status; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>d. Have you ever Missed work because of your drug use?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="missed_work_yes" name="missed_work" value="yes">
                            <label for="missed_work_yes">Yes</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="missed_work_no" name="missed_work" value="no">
                            <label for="missed_work_no">No</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="missed_work_na" name="missed_work" value="na">
                            <label for="missed_work_na">N/A</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>e. Have you ever been fired from your work because of your drug use?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="fired_yes" name="fired_work" value="yes">
                            <label for="fired_yes">Yes</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="fired_no" name="fired_work" value="no">
                            <label for="fired_no">No</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="fired_na" name="fired_work" value="na">
                            <label for="fired_na">N/A</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Family Relationships Section -->
            <div class="form-section">
                <div class="section-title">7. FAMILY RELATIONSHIPS</div>

                <div class="form-group">
                    <label>How is your relationship with your family?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="family_poor" name="family_relationship" value="poor">
                            <label for="family_poor">Poor</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="family_fair" name="family_relationship" value="fair">
                            <label for="family_fair">Fair</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="family_good" name="family_relationship" value="good">
                            <label for="family_good">Good</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Family and Social Support Section -->
            <div class="form-section">
                <div class="section-title">8. FAMILY AND SOCIAL SUPPORT</div>

                <div class="form-group">
                    <label>a. Do you have a person(s) who regularly depends on you for food and shelter?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="dependents_yes" name="has_dependents" value="yes">
                            <label for="dependents_yes">Yes</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="dependents_no" name="has_dependents" value="no">
                            <label for="dependents_no">No</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>b. If yes, who are the persons that depend on you?</label>
                    <div class="checkbox-group">
                        <?php
                        $dependent_options = ["Spouse", "Children", "Parent", "Sibling", "Other"];
                        foreach($dependent_options as $option):
                        ?>
                        <div class="checkbox-item">
                            <input type="checkbox" id="dependent_<?php echo strtolower($option); ?>" name="dependents[]" value="<?php echo $option; ?>">
                            <label for="dependent_<?php echo strtolower($option); ?>"><?php echo $option; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" id="dependent_other_specify" name="dependent_other_specify" placeholder="Specify other" class="hidden">
                </div>

                <div class="form-group">
                    <label>c. Does someone contribute to your support in any way?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="support_yes" name="has_support" value="yes">
                            <label for="support_yes">Yes</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="support_no" name="has_support" value="no">
                            <label for="support_no">No</label>
                        </div>
                    </div>
                </div>

                <div id="support_table_container" class="hidden">
                    <table>
                        <thead>
                            <tr>
                                <th>Support</th>
                                <th>Cash</th>
                                <th>Food</th>
                                <th>Shelter</th>
                                <th>Psychological</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $support_relationships = [
                                "Spouse/Partner", "Mother", "Father", "Brother", "Sister",
                                "Child", "Peer educator/Outreach worker", "Other"
                            ];
                            foreach($support_relationships as $relationship):
                            ?>
                            <tr>
                                <td><?php echo $relationship; ?></td>
                                <td><input type="checkbox" name="support_cash_<?php echo strtolower(str_replace(['/', ' '], ['_', '_'], $relationship)); ?>" value="1"></td>
                                <td><input type="checkbox" name="support_food_<?php echo strtolower(str_replace(['/', ' '], ['_', '_'], $relationship)); ?>" value="1"></td>
                                <td><input type="checkbox" name="support_shelter_<?php echo strtolower(str_replace(['/', ' '], ['_', '_'], $relationship)); ?>" value="1"></td>
                                <td><input type="checkbox" name="support_psychological_<?php echo strtolower(str_replace(['/', ' '], ['_', '_'], $relationship)); ?>" value="1"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <input type="text" id="support_other_specify" name="support_other_specify" placeholder="Specify other" class="hidden">
                </div>
            </div>

            <!-- GBV Section -->
            <div class="form-section">
                <div class="section-title">10. GBV</div>
                <p>Use national GBV tools</p>
            </div>


            <!-- Legal and Criminal status Section -->
            <div class="form-section">
                <div class="section-title">11. LEGAL/CRIMINAL STATUS</div>

                <div class="form-group">
                    <label>a. Have you commited and/or arrested for the following case for drugs in the last 30 days?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="case_yes" name="has_case" value="yes">
                            <label for="case_yes">Yes</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="case_no" name="has_case" value="no">
                            <label for="case_no">No</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>b. If yes, do you have any on-going case?</label>
                    <div class="checkbox-group">
                        <?php
                        $case_options = ["Committed", "Arrested"];
                        foreach($case_options as $option):
                        ?>
                        <div class="checkbox-item">
                            <input type="checkbox" id="dependent_<?php echo strtolower($option); ?>" name="dependents[]" value="<?php echo $option; ?>">
                            <label for="crime_<?php echo strtolower($option); ?>"><?php echo $option; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" id="crime_other_specify" name="crime_other_specify" placeholder="Specify other" class="hidden">
                </div>

                <div class="form-group">
                    <label>c. Did you have any committed case or arrests in the last 30 days?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="commited_arrests_yes" name="has_support" value="yes">
                            <label for="commited_arrests_yes">Yes</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="commited_arrests_no" name="has_commited_arrests" value="no">
                            <label for="commited_arrests_no">No</label>
                        </div>
                    </div>
                </div>

                <div id="support_table_container" class="hidden">
                    <table>
                        <thead>
                            <tr>
                                <th>Committed</th>
                                <th>Arrests</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $commited_arrests_cases = [
                                "Shoplifting/vandalism", "Drunk and disorderly", "Drug possesion", "Drug peddling", "Weapons offense",
                                "Burglary", "Robbery", "Assault", "Rape", "Murder", "Sex work", "Fraud/forgery scrap off"
                            ];
                            foreach($commited_arrests_cases as $case):
                            ?>
                            <tr>
                                <td><?php echo $case; ?></td>
                                <td><input type="checkbox" name="case_committed_<?php echo strtolower(str_replace(['/', ' '], ['_', '_'], $case)); ?>" value="1"></td>
                                <td><input type="checkbox" name="case_arrested_<?php echo strtolower(str_replace(['/', ' '], ['_', '_'], $case)); ?>" value="1"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <input type="text" id="case_other_specify" name="case_other_specify" placeholder="Specify other" class="hidden">
                </div>
            </div>
            <div class="form-group">
                <label for="therapist_name">Therapist Name</label>
                <input type="text" name="therapist_name">
            </div>
            <div class="form-group">
                <label for="service_date">Date of service</label>
                <input type="date" name="service_date">
            </div>
            <button type="submit" class="btn-submit">Submit Form</button>
        </form>
    </div>

    <script>
        // JavaScript for dynamic form behavior
        document.addEventListener('DOMContentLoaded', function() {
            // Show/hide "Other" text inputs based on selection
            const otherInputs = [
                { radio: 'other_sex', input: 'other_sex_specify' },
                { radio: 'marital_other', input: 'marital_other_specify' },
                { radio: 'education_other', input: 'education_other_specify' },
                { radio: 'dependent_other', input: 'dependent_other_specify' }
            ];

            otherInputs.forEach(item => {
                const radio = document.getElementById(item.radio);
                const input = document.getElementById(item.input);

                if (radio && input) {
                    radio.addEventListener('change', function() {
                        input.classList.toggle('hidden', !this.checked);
                    });
                }
            });

            // Show/hide "Specify" text inputs based on checkbox selection
            const otherCheckboxes = [
                { checkbox: 'living_other', input: 'living_other_specify' }
            ];

            otherCheckboxes.forEach(item => {
                const checkbox = document.getElementById(item.checkbox);
                const input = document.getElementById(item.input);

                if (checkbox && input) {
                    checkbox.addEventListener('change', function() {
                        input.classList.toggle('hidden', !this.checked);
                    });
                }
            });

            // Show/hide treatment specify field
            const treatmentYes = document.getElementById('treatment_yes');
            const treatmentSpecify = document.getElementById('treatment_specify');

            if (treatmentYes && treatmentSpecify) {
                treatmentYes.addEventListener('change', function() {
                    treatmentSpecify.classList.toggle('hidden', !this.checked);
                });
            }

            // Show/hide income specify field
            const incomeYes = document.getElementById('income_yes');
            const incomeSpecify = document.getElementById('income_specify');

            if (incomeYes && incomeSpecify) {
                incomeYes.addEventListener('change', function() {
                    incomeSpecify.classList.toggle('hidden', !this.checked);
                });
            }

            // Show/hide support table
            const supportYes = document.getElementById('support_yes');
            const supportTable = document.getElementById('support_table_container');

            if (supportYes && supportTable) {
                supportYes.addEventListener('change', function() {
                    supportTable.classList.toggle('hidden', !this.checked);
                });
            }
        });
    </script>
</body>
</html>