create table other_prescriptions
create table administrationroutes
create table durationunits
counts to change password, loginpage to change password
1. counts/drugstocked.php
2. settings
3. Backup direct on header
4. Root Password: LVCTHealth@20
5. Root Username: LVCTHealth
6. Root database: iorpms
7. Root host: localhost

# LVCTHealth colours: #722182; Purple
# Social links
    ; https://web.facebook.com/LVCTHealth/?_rdc=1&_rdr#
    ; https://www.youtube.com/user/TheLVCT
    ; https://x.com/LVCTKe
    ; https://www.instagram.com/lvct_health/
    ; https://www.linkedin.com/company/lvcthealth/


Tables changes
#tblusers
username and email = unique
# tables added:
; treatment_stage
: psychiatric_rx_problems
; Offences
; psycho_followup_visits
; living_conditions
; employment_status
; psychosocial_interventions
; integration_status
; referral_linkage_services
; psychosocial_outcomes
; nursing_services

Edit patients table
Add column:
ALTER TABLE patients
remove status_change_notes before import
ADD COLUMN psycho_social_tca DATE DEFAULT NULL,
ADD COLUMN psychiatric_tca DATE DEFAULT NULL,
ADD COLUMN nursing_tca DATE DEFAULT NULL,
ADD COLUMN nutrition_tca DATE DEFAULT NULL,
ADD COLUMN laboratory_tca DATE DEFAULT NULL,
ADD COLUMN records_tca DATE DEFAULT NULL,
ADD COLUMN peer_tca DATE DEFAULT NULL,
ADD COLUMN admin_tca DATE DEFAULT NULL;

-- update the columns
UPDATE patients
SET psycho_social_tca = next_appointment,
    psychiatric_tca = next_appointment,
    nursing_tca = next_appointment,
    nutrition_tca = next_appointment,
    laboratory_tca = next_appointment,
    records_tca = next_appointment,
    peer_tca = next_appointment,
    admin_tca = next_appointment
WHERE next_appointment IS NOT NULL;

# All names:
$service_provider

For future inserts/updates, you'll need to handle this in your PHP code:

// Example for INSERT
$psycho_social_tca = !empty($_POST['psycho_social_tca']) ? $_POST['psycho_social_tca'] : $next_appointment;
$psychiatric_tca = !empty($_POST['psychiatric_tca']) ? $_POST['psychiatric_tca'] : $next_appointment;
// ... and so on for other fields

// In your INSERT query
$sql = "INSERT INTO patients (next_appointment, psycho_social_tca, psychiatric_tca, ...)
        VALUES (?, ?, ?, ...)";


update table userroles
role = unique and add HRIO, Psychiatrist, Data Manager, then id - PK AI



userrole = ('Admin', 'Pharmacist', 'Laboratory Technologist', 'Clinician', 'Psychologist', 'HRIO', 'Peer Educator', Data Manager, Psychiatrist, Receptionist)

access on dashboard.php as follows:

Admin = (administrator, BackUp and Refresh, Patient Management, Pharmacy Management, Clinical Management, Psychosocial Management, Laboratory Management, Referrals Management, Reports Management, Biometrics, Patients Summary, Daily Consumption Summary, Stocks Summary and Monthly Consumption Summary)
Pharmacist = (BackUp and Refresh, Patient Management, Pharmacy Management, Referrals Management, Reports Management, Biometrics, Patients Summary, Daily Consumption Summary, Stocks Summary and Monthly Consumption Summary)
Laboratory Technologist = (BackUp and Refresh, Patient Management, Pharmacy Management, Clinical Management, Psychosocial Management, Laboratory Management, Referrals Management, Reports Management, Biometrics, Patients Summary, Daily Consumption Summary, Stocks Summary and Monthly Consumption Summary)
Clinician = (BackUp and Refresh, Patient Management, Clinical Management, Laboratory Management, Referrals Management, Reports Management, Biometrics, Patients Summary)
Psychologist = (BackUp and Refresh, Patient Management, Psychosocial Management, Referrals Management, Reports Management, Biometrics, Patients Summary)
HRIO = (BackUp and Refresh, Patient Management, Referrals Management, Reports Management, Biometrics, Patients Summary)
Peer Educator = (BackUp and Refresh, Patient Management, Referrals Management, Biometrics)
Data Manager = (BackUp and Refresh, Patient Management, Reports Management, Biometrics, Patients Summary, Daily Consumption Summary)
Psychiatrist = (BackUp and Refresh, Patient Management, Clinical Management, Psychosocial Management, Laboratory Management, Referrals Management, Reports Management, Biometrics, Patients Summary)
Receptionist = (BackUp and Refresh, Patient Management, Referrals Management, Reports Management, Biometrics)

Administrator
#Backup - ../backup/backup.php #no target
#Home - ../dashboard/dashboard.php #no target
#Add User ../public/user_registration.php

Backup and refresh
#Backup - ../backup/backup.php #no target
#Home - ../dashboard/dashboard.php #no target
#Recreate tables 


What is New
# Ease navigation interface
# Auto logout for enhanced security
# Login, logout access log
# 

# add table stocks

# edit table medical_history add comp_date AUTO timestamp
