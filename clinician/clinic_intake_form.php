<?php
  include '../includes/header.php';

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>CSS Responsive Navigation Bar</title>
        <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
        <style>
            table {
            width: 100%; /* Set table width to 100% */
            border-collapse: none; /* Remove borders between cells for cleaner look */
            margin: 20px;
            }

            th, td {
                padding: 5px; /* Add some padding for readability */
                /* Set font size for better scaling on different screens */
                font-size: 18px;
            }

            th {
                background-color: #2C3162; /* Light background for headers */
                color: white;
            }

            /* Style inputs and selects to fit cell dimensions */
            input[type="number"],
            input[type="date"],
            select {
                width: 100%; /* Match table cell width */
                border: 1px solid #ddd; /* Add border */
                padding: 5px; /* Add some padding */
                box-sizing: border-box; /* Ensure padding doesn't affect width */
                /* Set font size to match table cells */
                font-size: 0.8rem;
            }

            /* Style multi-select dropdowns for scrolling */
            select[multiple] {
                height: 30px; /* Set a fixed height for the dropdown */
                overflow-y: scroll; /* Enable scrolling for long lists */
            }

            /* Optional: Responsive adjustments for smaller screens */

            @media (max-width: 768px) {
                th, td {
                    font-size: 0.7rem; /* Adjust font size for smaller screens */
                }
            }

        </style>
    </head>
<body>
    <h2>DRUG USE HISTORY FORM</h2>
<table>
    <thead>
        <tr>
            <th>Type of Drug</th>
            <th>Age at first use of drug</th>
            <th>Duration of use(yrs)</th>
            <th>Frequency of use in last 30 days</th>
            <th>Quantity used regularly <br>(within past 30 days)</th>
            <th>Usual route of <br>administration</th>
            <th>Date & Time last used <br>(within past 30 days)</th>
            </tr>
    </thead>
    <tbody>
        <tr>
            <td>a. Heroin</td>
            <td><input type="number" name = "" class="hero_age"></td>
            <td><input type="number" name = "" class="hero_yrs"></td>
            <td>
                <select name="hero_freq" class="hero_freq">
                    <option value="">Select Frequency</option>
                    <option value="Never">Oral</option>
                    <option value="Once or Twice">Once or Twice</option>
                    <option value="Weekly">SmoWeekly</option>
                    <option value="Almost Daily">Almost Daily</option>
                    <option value="Daily">Almost Daily</option>
                </select>
            </td>
            <td><input type="number" name = "" class="hero_qty"></td>
            <td>
                <select name="hero_sroute[]" multiple class="hero_route">
                    <option value="">Select all Routes</option>
                    <option value="oral">Oral</option>
                    <option value="hero2">Nasal</option>
                    <option value="hero3">Smoking</option>
                    <option value="hero3">Injection</option>
                </select>

            </td>
            <td><input type="date" name = "" class="hero_date"></td>
            </tr>
            <!--ROW TWO-->
            <tr>
            <td>b. Canabis Sativa</td>
            <td><input type="number" name = "" class="hero_age"></td>
            <td><input type="number" name = "" class="hero_yrs"></td>
            <td>
                <select name="hero_freq" class="hero_freq">
                    <option value="">Select Frequency</option>
                    <option value="Never">Oral</option>
                    <option value="Once or Twice">Once or Twice</option>
                    <option value="Weekly">SmoWeekly</option>
                    <option value="Almost Daily">Almost Daily</option>
                    <option value="Daily">Almost Daily</option>
                </select>
            </td>
            <td><input type="number" name = "" class="hero_qty"></td>
            <td>
                <select name="hero_sroute[]" multiple class="hero_route">
                    <option value="">Select all Routes</option>
                    <option value="oral">Oral</option>
                    <option value="hero2">Nasal</option>
                    <option value="hero3">Smoking</option>
                    <option value="hero3">Injection</option>
                </select>

            </td>
            <td><input type="date" name = "" class="hero_date"></td>
            </tr>
            <tr>
            <td>c. Tobacco</td>
            <td><input type="number" name = "" class="hero_age"></td>
            <td><input type="number" name = "" class="hero_yrs"></td>
            <td>
                <select name="hero_freq" class="hero_freq">
                    <option value="">Select Frequency</option>
                    <option value="Never">Oral</option>
                    <option value="Once or Twice">Once or Twice</option>
                    <option value="Weekly">SmoWeekly</option>
                    <option value="Almost Daily">Almost Daily</option>
                    <option value="Daily">Almost Daily</option>
                </select>
            </td>
            <td><input type="number" name = "" class="hero_qty"></td>
            <td>
                <select name="hero_sroute[]" multiple class="hero_route">
                    <option value="">Select all Routes</option>
                    <option value="oral">Oral</option>
                    <option value="hero2">Nasal</option>
                    <option value="hero3">Smoking</option>
                    <option value="hero3">Injection</option>
                </select>

            </td>
            <td><input type="date" name = "" class="hero_date"></td>
            </tr>
            <tr>
            <td>d. Benzodiazepines</td>
            <td><input type="number" name = "" class="hero_age"></td>
            <td><input type="number" name = "" class="hero_yrs"></td>
            <td>
                <select name="hero_freq" class="hero_freq">
                    <option value="">Select Frequency</option>
                    <option value="Never">Oral</option>
                    <option value="Once or Twice">Once or Twice</option>
                    <option value="Weekly">SmoWeekly</option>
                    <option value="Almost Daily">Almost Daily</option>
                    <option value="Daily">Almost Daily</option>
                </select>
            </td>
            <td><input type="number" name = "" class="hero_qty"></td>
            <td>
                <select name="hero_sroute[]" multiple class="hero_route">
                    <option value="">Select all Routes</option>
                    <option value="oral">Oral</option>
                    <option value="hero2">Nasal</option>
                    <option value="hero3">Smoking</option>
                    <option value="hero3">Injection</option>
                </select>

            </td>
            <td><input type="date" name = "" class="hero_date"></td>
            </tr>
            <tr>
            <td>e. Alcohol</td>
            <td><input type="number" name = "" class="hero_age"></td>
            <td><input type="number" name = "" class="hero_yrs"></td>
            <td>
                <select name="hero_freq" class="hero_freq">
                    <option value="">Select Frequency</option>
                    <option value="Never">Oral</option>
                    <option value="Once or Twice">Once or Twice</option>
                    <option value="Weekly">SmoWeekly</option>
                    <option value="Almost Daily">Almost Daily</option>
                    <option value="Daily">Almost Daily</option>
                </select>
            </td>
            <td><input type="number" name = "" class="hero_qty"></td>
            <td>
                <select name="hero_sroute[]" multiple class="hero_route">
                    <option value="">Select all Routes</option>
                    <option value="oral">Oral</option>
                    <option value="hero2">Nasal</option>
                    <option value="hero3">Smoking</option>
                    <option value="hero3">Injection</option>
                </select>

            </td>
            <td><input type="date" name = "" class="hero_date"></td>
            </tr>
            <tr>
            <td>f. Amphetamine</td>
            <td><input type="number" name = "" class="hero_age"></td>
            <td><input type="number" name = "" class="hero_yrs"></td>
            <td>
                <select name="hero_freq" class="hero_freq">
                    <option value="">Select Frequency</option>
                    <option value="Never">Oral</option>
                    <option value="Once or Twice">Once or Twice</option>
                    <option value="Weekly">SmoWeekly</option>
                    <option value="Almost Daily">Almost Daily</option>
                    <option value="Daily">Almost Daily</option>
                </select>
            </td>
            <td><input type="number" name = "" class="hero_qty"></td>
            <td>
                <select name="hero_sroute[]" multiple class="hero_route">
                    <option value="">Select all Routes</option>
                    <option value="oral">Oral</option>
                    <option value="hero2">Nasal</option>
                    <option value="hero3">Smoking</option>
                    <option value="hero3">Injection</option>
                </select>

            </td>
            <td><input type="date" name = "" class="hero_date"></td>
            </tr>
            <tr>
            <td>g. Cocaine</td>
            <td><input type="number" name = "" class="hero_age"></td>
            <td><input type="number" name = "" class="hero_yrs"></td>
            <td>
                <select name="hero_freq" class="hero_freq">
                    <option value="">Select Frequency</option>
                    <option value="Never">Oral</option>
                    <option value="Once or Twice">Once or Twice</option>
                    <option value="Weekly">SmoWeekly</option>
                    <option value="Almost Daily">Almost Daily</option>
                    <option value="Daily">Almost Daily</option>
                </select>
            </td>
            <td><input type="number" name = "" class="hero_qty"></td>
            <td>
                <select name="hero_sroute[]" multiple class="hero_route">
                    <option value="">Select all Routes</option>
                    <option value="oral">Oral</option>
                    <option value="hero2">Nasal</option>
                    <option value="hero3">Smoking</option>
                    <option value="hero3">Injection</option>
                </select>

            </td>
            <td><input type="date" name = "" class="hero_date"></td>
            </tr>
            <tr>
            <td>h. Miraa</td>
            <td><input type="number" name = "" class="hero_age"></td>
            <td><input type="number" name = "" class="hero_yrs"></td>
            <td>
                <select name="hero_freq" class="hero_freq">
                    <option value="">Select Frequency</option>
                    <option value="Never">Oral</option>
                    <option value="Once or Twice">Once or Twice</option>
                    <option value="Weekly">SmoWeekly</option>
                    <option value="Almost Daily">Almost Daily</option>
                    <option value="Daily">Almost Daily</option>
                </select>
            </td>
            <td><input type="number" name = "" class="hero_qty"></td>
            <td>
                <select name="hero_sroute[]" multiple class="hero_route">
                    <option value="">Select all Routes</option>
                    <option value="oral">Oral</option>
                    <option value="hero2">Nasal</option>
                    <option value="hero3">Smoking</option>
                    <option value="hero3">Injection</option>
                </select>

            </td>
            <td><input type="date" name = "" class="hero_date"></td>
            </tr>
            <tr>
            <td>i. Glue</td>
            <td><input type="number" name = "" class="hero_age"></td>
            <td><input type="number" name = "" class="hero_yrs"></td>
            <td>
                <select name="hero_freq" class="hero_freq">
                    <option value="">Select Frequency</option>
                    <option value="Never">Oral</option>
                    <option value="Once or Twice">Once or Twice</option>
                    <option value="Weekly">SmoWeekly</option>
                    <option value="Almost Daily">Almost Daily</option>
                    <option value="Daily">Almost Daily</option>
                </select>
            </td>
            <td><input type="number" name = "" class="hero_qty"></td>
            <td>
                <select name="hero_sroute[]" multiple class="hero_route">
                    <option value="">Select all Routes</option>
                    <option value="oral">Oral</option>
                    <option value="hero2">Nasal</option>
                    <option value="hero3">Smoking</option>
                    <option value="hero3">Injection</option>
                </select>

            </td>
            <td><input type="date" name = "" class="hero_date"></td>
            </tr>
            <tr>
            <td>j. Barbiturates</td>
            <td><input type="number" name = "" class="hero_age"></td>
            <td><input type="number" name = "" class="hero_yrs"></td>
            <td>
                <select name="hero_freq" class="hero_freq">
                    <option value="">Select Frequency</option>
                    <option value="Never">Oral</option>
                    <option value="Once or Twice">Once or Twice</option>
                    <option value="Weekly">SmoWeekly</option>
                    <option value="Almost Daily">Almost Daily</option>
                    <option value="Daily">Almost Daily</option>
                </select>
            </td>
            <td><input type="number" name = "" class="hero_qty"></td>
            <td>
                <select name="hero_sroute[]" multiple class="hero_route">
                    <option value="">Select all Routes</option>
                    <option value="oral">Oral</option>
                    <option value="hero2">Nasal</option>
                    <option value="hero3">Smoking</option>
                    <option value="hero3">Injection</option>
                </select>

            </td>
            <td><input type="date" name = "" class="hero_date"></td>
            </tr>
            <tr>
            <td>k. Morphine</td>
            <td><input type="number" name = "" class="hero_age"></td>
            <td><input type="number" name = "" class="hero_yrs"></td>
            <td>
                <select name="hero_freq" class="hero_freq">
                    <option value="">Select Frequency</option>
                    <option value="Never">Oral</option>
                    <option value="Once or Twice">Once or Twice</option>
                    <option value="Weekly">SmoWeekly</option>
                    <option value="Almost Daily">Almost Daily</option>
                    <option value="Daily">Almost Daily</option>
                </select>
            </td>
            <td><input type="number" name = "" class="hero_qty"></td>
            <td>
                <select name="hero_sroute[]" multiple class="hero_route">
                    <option value="">Select all Routes</option>
                    <option value="oral">Oral</option>
                    <option value="hero2">Nasal</option>
                    <option value="hero3">Smoking</option>
                    <option value="hero3">Injection</option>
                </select>

            </td>
            <td><input type="date" name = "" class="hero_date"></td>
            </tr>
            <tr>
            <td>l. Others</td>
            <td><input type="number" name = "" class="hero_age"></td>
            <td><input type="number" name = "" class="hero_yrs"></td>
            <td>
                <select name="hero_freq" class="hero_freq">
                    <option value="">Select Frequency</option>
                    <option value="Never">Oral</option>
                    <option value="Once or Twice">Once or Twice</option>
                    <option value="Weekly">SmoWeekly</option>
                    <option value="Almost Daily">Almost Daily</option>
                    <option value="Daily">Almost Daily</option>
                </select>
            </td>
            <td><input type="number" name = "" class="hero_qty"></td>
            <td>
                <select name="hero_sroute[]" multiple class="hero_route">
                    <option value="">Select all Routes</option>
                    <option value="oral">Oral</option>
                    <option value="hero2">Nasal</option>
                    <option value="hero3">Smoking</option>
                    <option value="hero3">Injection</option>
                </select>

            </td>
            <td><input type="date" name = "" class="hero_date"></td>
            </tr>
        </tbody>
</table>
</body>
</html>